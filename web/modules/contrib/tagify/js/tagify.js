(function ($, Drupal, drupalSettings) {
  'use strict';
  Drupal.behaviors.tagifyAutocomplete = {
    attach: function attach(context, settings) {
      // see https://github.com/yairEO/tagify#ajax-whitelist
      var inputSelector = $('input.tagify-widget');

      let tagify_args = {
        dropdown: {
          enabled: 1,
          highlightFirst: true,
          fuzzySearch: false,
        },
        whitelist: [],
      }

      inputSelector.once('tagify-widget').each(function (e) {
        // @todo create a setting form in Drupal to have an editable option.
        var input = this,
          tagify = new Tagify(input, tagify_args),
          controller;

        // avoid creating tag when the entity reference is not existing.
        tagify.settings.enforceWhitelist = !$(this).hasClass("autocreate");
        tagify.settings.skipInvalid = !!$(this).hasClass("autocreate");
        tagify.settings.maxTags = $(this).hasClass("limited") ? 1 : Infinity;

        // Bind "DragSort" to Tagify's main element and tell
        // it that all the items with the below "selector" are "draggable".
        new DragSort(tagify.DOM.scope, {
          selector: '.' + tagify.settings.classNames.tag,
          callbacks: {
            dragEnd: onDragEnd
          }
        });

        // Must update Tagify's value according to the re-ordered nodes in the DOM.
        function onDragEnd(e){
          tagify.updateValueByDOMTags()
        }

        // onInput event.
        var onInput = Drupal.debounce(function (e) {
          var value = e.detail.value;
          tagify.whitelist = null;

          // https://developer.mozilla.org/en-US/docs/Web/API/AbortController/abort
          controller && controller.abort();
          controller = new AbortController();

          // Show loading animation meanwhile the dropdown suggestions are hided.
          tagify.loading(true);
          fetch($(input).attr('data-autocomplete-url') + '?q=' + encodeURIComponent(value), {signal: controller.signal})
            .then(res => res.json())
            .then(function (newWhitelist) {
              var newWhitelistData = [];
              newWhitelist.forEach(function (current) {
                newWhitelistData.push({
                  value: current.label,
                  entity_id: current.entity_id,
                  ...current.attributes
                });
              });
              // build the whitelist with the values coming from the fetch
              tagify.whitelist = newWhitelistData; // update whitelist Array in-place
              tagify.loading(false).dropdown.show(value) // render the suggestions dropdown
            });
        }, 500);

        // tag added callback
        function onAddTag(e){
          tagify.off('add', onAddTag) // exmaple of removing a custom Tagify event
        }

        // listen to any keystrokes which modify tagify's input
        tagify.on('input', onInput)
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
