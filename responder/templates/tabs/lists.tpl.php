<?php
  use RavMesser\Plugin\API as PluginAPI;

  $rmp_systems_names = PluginAPI::getDisplaySystemsNames(); // current system first (PR1-I5)
  $rmp_systems_count = count( $rmp_systems_names );
?>

<h1>
  <?php esc_html_e( 'נמענים', 'responder' ); ?>
</h1>

<p>
  <?php esc_html_e( 'בחרו רשימה מבין רשימות רב מסר שלכם וצפו בפרטי הנמענים', 'responder' ); ?>
</p>

<?php if ( $rmp_systems_count > 1 ) : ?>
  <fieldset class="rmp-lists-system-switcher">
	<h3><?php esc_html_e( 'הצגת רשימות ממערכת:', 'responder' ); ?></h3>
	<p>
	  <label>
		<input
		  type="radio"
		  name="rmp_choose_system"
		  value="responder_live"
		  checked
		/>
		<?php esc_html_e( 'רב מסר', 'responder' ); ?>
	  </label>
	</p>
	<p>
	  <label>
		<input
		  type="radio"
		  name="rmp_choose_system"
		  value="responder"
		/>
		<?php esc_html_e( 'רב מסר הישנה', 'responder' ); ?>
	  </label>
	</p>
  </fieldset>
<?php endif ?>

<br>
<br>
<div style="width: 300px; display: flex; flex-direction: column">
  <?php foreach ( $rmp_systems_names as $system_name ) : ?>
		<?php $rmp_lists = PluginAPI::run( $system_name )->getLists(); ?>
	<select data-id="<?php echo esc_attr( $system_name ); ?>" class="rmp-lists-select hidden">
	  <option disabled selected value=""><?php esc_html_e( 'בחירת רשימה', 'responder' ); ?></option>

		<?php foreach ( $rmp_lists as $list ) : ?>
		<option value="<?php echo esc_attr( $list['id'] ); ?>">
			<?php echo esc_html( $list['name'] ); ?>
		</option>
	  <?php endforeach ?>
	</select>
  <?php endforeach ?>
</div>

<p id="rmp-loading-subscribers" hidden>
  <?php esc_html_e( 'טוען נמענים...', 'responder' ); ?>
</p>

<div id="rmp-subscribers-error" class="notice notice-error inline" hidden><p></p></div>

<div id="rmp-subscribers-table-wrap" class="rtl" hidden>
  <h4 class="total-count">
	<?php esc_html_e( 'סך הכל נמענים ברשימה:', 'responder' ); ?>
	<span></span>
  </h4>
  <p class="description rmp-paged-note" data-system="responder_live" hidden>
	<?php esc_html_e( 'מוצגים עד 100 נמענים בכל עמוד, והחיפוש מתבצע במערכת רב מסר. העתקה, ייצוא (CSV, Excel) והדפסה כוללים רק את העמוד המוצג.', 'responder' ); ?>
  </p>
  <p class="description rmp-paged-note" data-system="responder" hidden>
	<?php esc_html_e( 'מוצגים עד 500 נמענים בכל עמוד. החיפוש מוצא נמען לפי כתובת מייל מלאה או מספר נמען. העתקה, ייצוא (CSV, Excel) והדפסה כוללים רק את העמוד המוצג.', 'responder' ); ?>
  </p>
  <table>
	<thead><tr></tr></thead>
	<tbody></tbody>
	<tfoot><tr></tr></tfoot>
  </table>
</div>

<script>
  var rmpDataTable = null;
  var rmpSelect2   = null;

  function rmpUpdateTable() {
	var selectedListId = jQuery(this).val();
	var chosenSystem   = jQuery(this).data('id');
	var $rmpTableWrap  = jQuery('#rmp-subscribers-table-wrap');
	var $rmpTableLoad  = jQuery('#rmp-loading-subscribers');

	var $rmpTableError = jQuery('#rmp-subscribers-error');

	$rmpTableWrap.attr('hidden', true);
	$rmpTableError.attr('hidden', true);
	$rmpTableLoad.removeAttr('hidden');

	var rmpShowTableError = function(data) {
		var message = (data && data.message) || <?php echo wp_json_encode( esc_html__( 'לא הצלחנו לטעון את הרשימה. נסו שוב בעוד כמה דקות.', 'responder' ) ); ?>;

		$rmpTableLoad.attr('hidden', true);
		$rmpTableError.find('p').text(message);
		$rmpTableError.removeAttr('hidden');
	};

	// PR1-I18: export names - "<list name> - <date>" (they were named after the admin page title).
	var rmpListName       = jQuery.trim(jQuery(this).find('option:selected').text()) || String(selectedListId);
	var rmpToday          = new Date();
	var rmpExportDate     = rmpToday.getFullYear() + '-' + ('0' + (rmpToday.getMonth() + 1)).slice(-2) + '-' + ('0' + rmpToday.getDate()).slice(-2);
	var rmpExportTitle    = rmpListName + ' - ' + rmpExportDate;
	var rmpExportFileName = rmpExportTitle.replace(/[\\\/:*?"<>|]+/g, '-'); // characters Windows does not allow in file names
	var rmpExportSheetName = rmpListName.replace(/[\[\]:*?\/\\]+/g, '-').slice(0, 31) || 'Sheet1'; // Excel: up to 31 characters, no []:*?/\

	// PR1-I18: Excel shows the sheet right-to-left (<sheetViews> goes before <cols> in the sheet XML).
	var rmpExcelRightToLeft = function(xlsx) {
		var sheet = xlsx.xl.worksheets['sheet1.xml'];
		var root  = sheet.documentElement;
		var ns    = root.namespaceURI;
		var views = sheet.createElementNS(ns, 'sheetViews');
		var view  = sheet.createElementNS(ns, 'sheetView');

		view.setAttribute('rightToLeft', '1');
		view.setAttribute('workbookViewId', '0');
		views.appendChild(view);
		root.insertBefore(views, root.getElementsByTagName('cols')[0] || root.getElementsByTagName('sheetData')[0] || null);
	};

	// PR1-I17: רב מסר lists are loaded a page at a time (large lists timed out in one call).
	var rmpPaged       = true; // both systems (רב מסר: search API, רב מסר הישנה: limit + offset)
	// רב מסר הישנה: 500 per page - the most the V1 API returns per call (no page numbers there, fewer clicks).
	var rmpPageSize    = chosenSystem === 'responder' ? 500 : 100;
	var rmpRequestData = { list_id: selectedListId, system_name: chosenSystem, };

	if (rmpPaged) {
		rmpRequestData = _.extend({}, rmpRequestData, { paged: 1, offset: 0, size: rmpPageSize, search: '' });
	}

	// DataTables' answer for one page: totals from the list count, or estimated when unknown.
	var rmpToDataTablesPage = function(page, draw, search) {
		var rows     = page.rows || [];
		var received = _.isNumber(page.received) ? page.received : rows.length;
		var estimate = (page.offset || 0) + received + (received >= page.size ? 1 : 0);
		var matched  = _.isNumber(page.matched) ? page.matched : estimate;
		var total    = _.isNumber(page.count) ? page.count : matched;
		var filtered = search ? matched : total;

		return { draw: draw, recordsTotal: total, recordsFiltered: filtered, data: rows };
	};

	jQuery.RMP_AJAX('getSubscribersSheetByListId', rmpRequestData, true)
	  .done(function(sheet) {
		// PR1-I17: the list could not be loaded - the server sends a JSON error (success: false).
		if (!sheet || sheet.success === false) {
			rmpShowTableError(sheet && sheet.data);
			return;
		}

		var headerColumns = '';
		var rmpDataTableConfig = {
		  'data': sheet.rows,
		  'dom': 'Bfrtip',
		  // PR1-I18: no PDF (pdfMake has no Hebrew font - every letter was an empty box; "הדפסה" saves a PDF instead),
		  // CSV with a UTF-8 BOM (Excel showed gibberish), files and titles named after the list and the date,
		  // and the Excel sheet right-to-left.
		  'buttons': [
			{ extend: 'copy', title: rmpExportTitle },
			{ extend: 'csv', bom: true, charset: 'utf-8', filename: rmpExportFileName },
			{ extend: 'excel', title: rmpExportTitle, filename: rmpExportFileName, sheetName: rmpExportSheetName, customize: rmpExcelRightToLeft },
			{ extend: 'print', title: rmpExportTitle }
		  ],
		  'language': {
			'lengthMenu': 'מציג _MENU_ תוצאות בעמוד',
			'zeroRecords': 'לא נמצאו תוצאות',
			'info': 'מציג עמוד _PAGE_ מתוך _PAGES_',
			'infoEmpty': 'לא נמצאו תוצאות',
			'infoFiltered': '(סוננן מ _MAX_ סה״כ תוצאות)',
			'decimal': '',
			'emptyTable': 'אין מידע זמין',
			'infoPostFix': '',
			'thousands': ',',
			'loadingRecords': 'טוען...',
			'processing': 'מעבד...',
			'search': 'חיפוש:',
			'paginate': {
			  'first': 'רשאון',
			  'last': 'אחרון',
			  'next': 'הבא',
			  'previous': 'קודם'
			},
			'aria': {
			  'sortAscending': ': הפעל למיין את העמודה בסדר עולה',
			  'sortDescending': ': הפעל למיין את העמודה בסדר יורד'
			}
		  }
		};

		_.each(sheet.columns, function(column) {
		  headerColumns += '<th>' + column + '</th>';
		});

		if (rmpDataTable) {
		  rmpDataTable.destroy();
		}

		$rmpTableWrap
		  .find('table thead tr')
			.empty()
			.append(headerColumns)
		  .end()
		  .find('table tbody')
			.empty()
		  .end()
		  .find('table tfoot tr')
			.empty()
			.append(headerColumns)
		  .end();

		if (rmpPaged) {
			var rmpFirstPage    = sheet;
			var rmpTotalUnknown = !_.isNumber(sheet.count); // רב מסר הישנה: the API has no total

			if (rmpTotalUnknown) {
				rmpDataTableConfig.pagingType   = 'simple';
				rmpDataTableConfig.infoCallback = function(settings) {
					return <?php echo wp_json_encode( esc_html__( 'עמוד', 'responder' ) ); ?> + ' ' + (Math.floor(settings._iDisplayStart / settings._iDisplayLength) + 1);
				};
			}

			rmpDataTableConfig = _.extend({}, rmpDataTableConfig, {
				'data': undefined,
				'serverSide': true,
				'processing': true,
				'ordering': false,
				'searchDelay': 700,
				'pageLength': rmpPageSize,
				'lengthMenu': [ rmpPageSize ],
				'ajax': function(dtRequest, callback) {
					var search = (dtRequest.search && dtRequest.search.value) || '';

					// The first page was already loaded with the columns.
					if (rmpFirstPage && dtRequest.start === 0 && dtRequest.length === rmpFirstPage.size && !search) {
						var firstPage = rmpFirstPage;
						rmpFirstPage = null;
						return callback(rmpToDataTablesPage(firstPage, dtRequest.draw, ''));
					}

					jQuery.RMP_AJAX('getSubscribersSheetByListId', _.extend({}, rmpRequestData, {
						offset: dtRequest.start,
						size: dtRequest.length,
						search: search
					}), true)
					  .done(function(page) {
						if (!page || page.success === false) {
							rmpShowTableError(page && page.data);
							return callback({ draw: dtRequest.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
						}

						$rmpTableError.attr('hidden', true);
						callback(rmpToDataTablesPage(page, dtRequest.draw, search));
					  })
					  .fail(function(xhr) {
						rmpShowTableError(xhr && xhr.responseJSON && xhr.responseJSON.data);
						callback({ draw: dtRequest.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
					  });
				}
			});
			delete rmpDataTableConfig.data;
		}

		rmpDataTable = $rmpTableWrap.find('table').DataTable(rmpDataTableConfig);

		$rmpTableWrap.find('.rmp-paged-note').attr('hidden', true).filter('[data-system="' + chosenSystem + '"]').attr('hidden', !rmpPaged);
		$rmpTableWrap.find('.total-count').attr('hidden', !_.isNumber(sheet.count));
		jQuery('#rmp-subscribers-table-wrap .total-count span').text(_.isNumber(sheet.count) ? sheet.count : '');
		$rmpTableLoad.attr('hidden', true);
		$rmpTableWrap.removeAttr('hidden');
	  })
	  // PR1-I17: never leave "טוען נמענים..." on screen - show why the list did not load.
	  .fail(function(xhr) {
		rmpShowTableError(xhr && xhr.responseJSON && xhr.responseJSON.data);
	  });
  }

  function rmpUpdateLists(chosenSystem) {
	var $rmpListsSelect = jQuery('.rmp-lists-select'),
		$rmpTableWrap   = jQuery('#rmp-subscribers-table-wrap'),
		$rmpTableLoad   = jQuery('#rmp-loading-subscribers');

	$rmpTableWrap.attr('hidden', true);
	$rmpTableLoad.attr('hidden', true);
	jQuery('#rmp-subscribers-error').attr('hidden', true);

	$rmpListsSelect
	  .select2({
		containerCssClass: 'hidden'
	  })
	  .prop('selectedIndex', 0)
	  .filter('[data-id="' + chosenSystem + '"]')
		.select2({
			dir: 'rtl',
			minimumResultsForSearch: 5,
			containerCssClass: '',
			width: '100%'
		  });
  }

  jQuery(document).ready(function() {
	var $rmpListsSelect  = jQuery('.rmp-lists-select'),
		$rmpChosenSystem = jQuery('[name="rmp_choose_system"]');

	jQuery.fn.dataTable.ext.errMode = 'none';

	$rmpListsSelect
	  .on('change', rmpUpdateTable)
	  .select2({
		containerCssClass: 'hidden'
	  })
	  .first()
		.select2({
		  dir: 'rtl',
		  minimumResultsForSearch: 5,
		  containerCssClass: '',
		  width: '100%'
		});

	$rmpChosenSystem
	  .on('change', function(event) {
		rmpUpdateLists(event.currentTarget.value);
	  });
  });
</script>
