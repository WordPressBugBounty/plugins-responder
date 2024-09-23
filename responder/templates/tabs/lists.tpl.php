<?php
  use RavMesser\Plugin\API as PluginAPI;

  $rmp_systems_names = PluginAPI::getConnectedSystemsNames();
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
		  value="responder"
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
		  value="responder_live"
		/>
		<?php esc_html_e( 'רב מסר - מערכת חדשה', 'responder' ); ?>
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

<div id="rmp-subscribers-table-wrap" class="rtl" hidden>
  <h4 class="total-count">
	<?php esc_html_e( 'סך הכל נמענים ברשימה:', 'responder' ); ?>
	<span></span>
  </h4>
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

	$rmpTableWrap.attr('hidden', true);
	$rmpTableLoad.removeAttr('hidden');

	jQuery.RMP_AJAX('getSubscribersSheetByListId', { list_id: selectedListId, system_name: chosenSystem, })
	  .done(function(sheet) {
		var headerColumns = '';
		var rmpDataTableConfig = {
		  'data': sheet.rows,
		  'dom': 'Bfrtip',
		  'buttons': [
			'copy', 'csv', 'excel', 'pdf', 'print'
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

		rmpDataTable = $rmpTableWrap.find('table').DataTable(rmpDataTableConfig);

		jQuery('#rmp-subscribers-table-wrap .total-count span').html(sheet.count);
		$rmpTableLoad.attr('hidden', true);
		$rmpTableWrap.removeAttr('hidden');
	  });
  }

  function rmpUpdateLists(chosenSystem) {
	var $rmpListsSelect = jQuery('.rmp-lists-select'),
		$rmpTableWrap   = jQuery('#rmp-subscribers-table-wrap'),
		$rmpTableLoad   = jQuery('#rmp-loading-subscribers');

	$rmpTableWrap.attr('hidden', true);
	$rmpTableLoad.attr('hidden', true);

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
