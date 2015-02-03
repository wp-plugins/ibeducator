(function($) {
	function Autocomplete( input, options ) {
		this.input = $(input);
		this.options = options;
		this.running = false;

		this.init();
	}

	Autocomplete.prototype.init = function() {
		var that = this;

		this.hidden = this.input.parent().find('.ib-edu-autocomplete-value');

		this.input.on('keyup', function() {
			that.autocomplete();
		});

		this.input.on('focusin', function() {
			that.autocomplete();
		});

		this.input.on('click', function(e) {
			e.stopPropagation();
		});

		this.choicesDiv = $('<div class="ib-edu-autocomplete-choices"></div>');
		this.choicesDiv.appendTo('body');

		this.choicesDiv.on('click', 'a', function(e) {
			e.preventDefault();
			e.stopPropagation();
			that.hidden.val(this.getAttribute('data-value'));
			that.input.val(this.innerHTML);
			that.clearChoices();
		});

		$('body').on('click', function() {
			that.clearChoices();
		});
	};

	Autocomplete.prototype.display = function(choices) {
		if (typeof choices !== 'object') {
			return;
		}

		var key;
		this.clearOtherChoices();
		this.clearChoices();

		this.choicesDiv.css({
			left: this.input.offset().left + 'px',
			top: ( this.input.offset().top + this.input.outerHeight() ) + 'px',
			width: this.input.outerWidth() + 'px',
			display: 'block'
		});

		for (key in choices) {
			if (!choices.hasOwnProperty(key)) continue;

			this.choicesDiv.append('<a data-value="' + key + '">' + choices[ key ] + '</a>');
		}
	};

	Autocomplete.prototype.autocomplete = function() {
		if (this.running) {
			return;
		}

		this.running = true;

		var inputValue = this.input.val();

		if (inputValue === '') {
			this.hidden.val('');
		}

		var that = this;

		$.ajax({
			type: 'get',
			cache: 'false',
			dataType: 'json',
			url: this.options.url,
			data: {
				input: inputValue,
				action: 'ib_educator_autocomplete',
				entity: this.options.entity,
				_wpnonce: this.options.nonce
			},
			success: function(response) {
				if (response) {
					that.display(response);
				}

				that.running = false;
			},
			error: function() {
				that.running = false;
			}
		});
	};

	Autocomplete.prototype.clearChoices = function() {
		this.choicesDiv.html('');
		this.choicesDiv.css('display', 'none');
	};

	Autocomplete.prototype.clearOtherChoices = function() {
		$('div.ib-edu-autocomplete-choices')
			.not(this.choicesDiv)
			.html('')
			.css('display', 'none');
	};

	window.ibEducatorAutocomplete = function(input, options) {
		var autocomplete = new Autocomplete(input, options);
	};
})(jQuery);