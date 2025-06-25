(function () {
    Craft.MediaflowField = Garnish.Base.extend({

        init: function (options) {
            this.$container = $('[data-id="' + options.namespace + 'mediaflow"]');
            this.$popupHtml = options.popupHtml;
            this.$trigger = this.$container.find(".mediaflow-trigger");
            this.$hiddenInput = this.$container.find(".mediaflow-value");
            this.$previewInput = this.$container.find(".mediaflow-preview");
            let self = this;

            this.$previewInput.find(".mediaflow-remove").each(function (index) {
                self.addListener(this, 'click', 'removeSelection');
            });

            this.addListener(this.$trigger, "click", "showPopup");

            window.addEventListener("message", function (event) {
                if (event.data.action === "updatePreview" && event.data.namespace === options.namespace) {
                    jsonData = JSON.stringify(event.data);
                    this.$hiddenInput.val(jsonData);
                    this.$popupWindow.close();
                    this.updatePreview(jsonData);
                }
            }.bind(this), false);
        },

        removeSelection: function (event) {
            let code = event.currentTarget.dataset.imgCode;
            let input = this.$hiddenInput.val();
            this.$hiddenInput.val(null);

            try {
                let json = JSON.parse(input);
                if (Array.isArray(json)) {
                    let filtered = json.filter((media) => ('code' in media) && (media.code != code));
                    this.$hiddenInput.val(JSON.stringify(filtered));
                }
            } catch (error) { }

            $(event.currentTarget).parents().eq(1).remove();
        },

        removePreview: function () {
            this.$previewInput.empty();
        },

        showPopup: function (ev) {
            ev.preventDefault();

            var width = 1000;
            var height = 500;

            var leftPosition = (screen.width) ? (screen.width - width) / 2 : 0;
            var topPosition = (screen.height) ? (screen.height - height) / 2 : 0;
            var settings = 'height=' + height + ',width=' + width + ',top=' + topPosition + ',left=' + leftPosition + ',resizable';

            this.$popupWindow = window.open("", "Mediaflow", settings);

            if (this.$popupWindow) {
                this.$popupWindow.document.open();
                this.$popupWindow.document.write(this.$popupHtml);
                this.$popupWindow.document.close();
            } else {
                alert("Popup blocked! Allow popups for this site.");
            }
        },

        updatePreview: function (data) {
            var json = JSON.parse(data);

            this.removePreview();
            if (!Array.isArray(json)) {
                json = [json];
            }

            json.forEach(media => {
                var mediaInstance = $('<div>', {
                    class: 'mediaflow-media-container',
                }).appendTo(this.$previewInput);

                if (media.basetype === 'video') {
                    $('<video>', {
                        width: 200,
                        controls: true,
                        poster: media.poster
                    }).append(
                        $('<source>', {
                            src: media.url,
                            type: 'video/' + media.filetype
                        })
                    ).appendTo(mediaInstance);
                } else {
                    $('<img>', {
                        width: 100,
                        src: media.url,
                        alt: media.altText || ''
                    }).appendTo(mediaInstance);
                }

                var labelDiv = $("<div class='mediaflow-label'>");
                var inner1 = $("<div class='label'><span class='title'>" + (media.name || 'no title') + "</span></div>");
                var inner2 = $("<a class='delete icon mediaflow-remove' title='Remove' data-img-code='" + media.id + "'></a>");
                this.addListener(inner2, "click", 'removeSelection');

                labelDiv.append(inner1);
                labelDiv.append(inner2);
                mediaInstance.append(labelDiv);
            });
        }
    });
})();
