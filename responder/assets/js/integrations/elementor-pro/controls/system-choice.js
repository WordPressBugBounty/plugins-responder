jQuery(window).on("elementor:init", function () {
	var systemChoiceItemView = elementor.modules.controls.Select.extend({
		onRender: function () {
			this.$systemChoiceSelect = this.$el.find('.elementor-control-input-wrapper select');
			this.toggleTutorialLink = this.toggleTutorialLink.bind(this);

			this.$systemChoiceSelect.on('change', this.toggleTutorialLink.bind(this));

			this.bugPatch();
			this.toggleTutorialLink();
		},

		bugPatch: function () {
			var labelBlock = this.model.get("label_block"),
				separator = this.model.get("separator"),
				connectedSystemsCount = this.model.get("connectedSystemsCount");

			if (connectedSystemsCount < 2) {
				return false;
			}

			// Wierd bug, if adding enqueue function to this control (PHP),
			// label_block setting is not added from this extended
			// control nor the $widget->add_control function
			// so here is the fix
			if (labelBlock) {
				this.$el.addClass("elementor-label-block");
			}

			// Same goes for this fix
			if (separator !== "") {
				this.$el.addClass("elementor-control-separator-" + separator);
			}
		},

		// Show Video tutorial link only for responder live system.
		toggleTutorialLink: function() {
			var $tutorialLink = this.$el.find('.elementor-control-field-tutorial-link');

			if ('responder_live' === this.$systemChoiceSelect.val()) {
				$tutorialLink.show();
			} else {
				$tutorialLink.hide();
			}
		}
	});

	elementor.addControlView("responder_system_choice", systemChoiceItemView);
});
