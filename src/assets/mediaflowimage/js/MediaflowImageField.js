class MediaflowImageField {
    static instances = {};
    static messageHandlerRegistered = false;

    constructor(fieldId, fieldHandle, createAssetUrl, viewDeltaName) {
        // console.log('MediaflowImageField initialized', fieldId, fieldHandle, viewDeltaName); // DEBUG
        this.fieldId = fieldId;
        this.fieldHandle = fieldHandle;
        this.createAssetUrl = createAssetUrl;
        this.inputHiddenName = viewDeltaName;
        this.popupWindow = null;
        this.activeButton = null;

        this.fieldId = this.inputHiddenName;

        // Register this instance
        MediaflowImageField.instances[this.fieldId] = this;

        this.init();

        // Register the global message handler only once
        if (!MediaflowImageField.messageHandlerRegistered) {
            window.addEventListener('message', MediaflowImageField.handleMessageGlobally);
            MediaflowImageField.messageHandlerRegistered = true;
        }
    }

    init() {
        const buttons = document.querySelectorAll(`.mediaflow-image-select[data-field-handle="${this.fieldHandle}"]`);

        if (buttons.length === 0) {
            console.error('No buttons found for field', this.fieldId);
            return;
        }

        buttons.forEach(button => {
            button.addEventListener('click', this.handleButtonClick.bind(this));
            // console.log('MediaflowImageField event listener added for', this.fieldId); // DEBUG
        });
    }

    // Static global message handler
    static async handleMessageGlobally(event) {
        if (!event.data || event.data.action !== 'updatePreview') {
            return;
        }
        // Try to find the correct instance by checking all activeButtons
        for (const fieldId in MediaflowImageField.instances) {
            const instance = MediaflowImageField.instances[fieldId];
            if (instance.popupWindow && event.source === instance.popupWindow && instance.activeButton) {
                await instance.handleMessage(event);
                break;
            }
        }
    }

    handleButtonClick(e) {
        e.preventDefault();
        e.stopPropagation();
        this.activeButton = e.currentTarget;

        const popupUrl = this.activeButton.dataset.popupUrl;
        this.popupWindow = window.open(popupUrl, 'mediaflow-image-selector', 'width=1024,height=600');
        if (this.popupWindow) {
            this.popupWindow.focus();
        }
    }

    async handleMessage(event) {
        const data = event.data;
        console.log('Mediaflow image selection received:', data);

        const fieldContainer = this.activeButton.closest('.field');
        if (!fieldContainer) {
            console.error('Could not find field container from button');
            return;
        }

        try {
            const result = await this.createAsset(data);
            if (result.success) {
                this.addAssetToField(fieldContainer, result.elements[0], result.details);
                // Save the entry via AJAX if possible, otherwise just notify the user
                if (window.draftEditor && typeof window.draftEditor.saveDraft === 'function') {
                    window.draftEditor.saveDraft().then(() => {
                        Craft.cp.displayNotice("Asset added! Continue editing your entry.");
                    });
                } else {
                    Craft.cp.displayNotice("Asset added!");
                }
            } else {
                throw new Error(result.error || 'Unknown error');
            }
        } catch (error) {
            console.error('Error creating image asset:', error);
            Craft.cp.displayError('Error creating image asset: ' + error.message);
        } finally {
            if (this.popupWindow) {
                this.popupWindow.close();
            }
            this.activeButton = null;
        }
    }

    async createAsset(data) {
        const formData = new FormData();
        formData.append('mediaflowImageId', data.id);
        formData.append('mediaflowImageUrl', data.url || data.download_url);
        formData.append('filename', data.filename || data.name);
        formData.append('title', data.title || data.name);
        formData.append('altText', data.altText || '');
        formData.append('entryUrl', this.activeButton.dataset.entryUrl);
        formData.append('entryTitle', this.activeButton.dataset.entryTitle);

        // Ensure volumeId is a number
        const volumeId = parseInt(this.activeButton.dataset.volumeId, 10);
        if (isNaN(volumeId) || volumeId <= 0) {
            throw new Error('Invalid volume ID configuration. Please check the field settings.');
        }
        formData.append('volumeId', volumeId);
        formData.append('folderPath', this.activeButton.dataset.folderPath || '');
        formData.append('fieldId', this.fieldId);

        // New fields for entry context
        formData.append('entryId', this.activeButton.dataset.entryId || '');
        formData.append('draftId', this.activeButton.dataset.draftId || '');
        formData.append('canonicalId', this.activeButton.dataset.canonicalId || '');
        formData.append('siteId', this.activeButton.dataset.siteId || '');
        formData.append('fieldHandle', this.activeButton.dataset.fieldHandle || '');

        // Get CSRF token
        const csrfTokenValue = window.Craft?.csrfTokenValue ||
            document.querySelector('input[name="CRAFT_CSRF_TOKEN"]')?.value;
        if (!csrfTokenValue) {
            throw new Error('Security token not found. Please refresh the page and try again.');
        }
        formData.append(window.Craft?.csrfTokenName || 'CRAFT_CSRF_TOKEN', csrfTokenValue);

        try {
            const response = await fetch(this.createAssetUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrfTokenValue,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const responseData = await response.json();

            if (!response.ok) {
                throw new Error(responseData.error || `HTTP error! status: ${response.status}`);
            }

            return responseData;
        } catch (error) {
            console.error('Image asset creation failed:', error);
            throw new Error(`Failed to create image asset: ${error.message}`);
        }
    }

    addAssetToField(fieldContainer, assetId, assetDetails) {
        if (!assetId) {
            // Do not create a hidden input if assetId is empty
            return;
        }
        const elementsContainer = fieldContainer.querySelector('.elements');
        if (!elementsContainer) {
            console.error('Could not find elements container');
            return;
        }

        // Always use the provided inputHiddenName for the hidden input name
        const inputName = this.inputHiddenName + '[]';

        const newAssetLi = window.$('<li/>').append(
            window.$('<div/>', {
                'id': 'fields-chip-' + assetId,
                'class': 'chip small element removable',
                'data-type': 'craft\\elements\\Asset',
                'data-id': assetId,
                'data-settings': JSON.stringify({
                    selectable: false,
                    id: 'fields-chip-' + assetId,
                    hyperlink: true,
                    showLabel: true,
                    showHandle: false,
                    showStatus: false,
                    showThumb: true,
                    size: 'small',
                    ui: 'chip',
                    context: 'field',
                    showDraftName: true,
                    showProvisionalDraftLabel: null
                }),
                'data-kind': 'image',
                'data-filename': assetDetails.filename,
                'data-site-id': window.Craft.siteId,
                'data-status': 'enabled',
                'data-label': assetDetails.title || assetDetails.filename,
                'data-url': assetDetails.url,
                'data-cp-url': '/admin/assets/edit/' + assetId + '-' + (assetDetails.filename || '').replace(/\.[^/.]+$/, ""),
                'tabindex': '0'
            }).append(
                window.$('<div/>', {
                    'class': 'thumb',
                    'data-sizes': 'calc(30rem/16)',
                    'data-srcset': assetDetails.url + '?transform=preview 30w, ' + assetDetails.url + '?transform=preview 60w'
                }).append(
                    window.$('<img/>', {
                        'sizes': 'calc(30rem/16)',
                        'srcset': assetDetails.url + '?transform=preview 30w, ' + assetDetails.url + '?transform=preview 60w',
                        'alt': ''
                    })
                )
            ).append(
                window.$('<div/>', {
                    'class': 'chip-content'
                }).append(
                    window.$('<craft-element-label/>', {
                        'id': 'fields-chip-' + assetId + '-label',
                        'class': 'label'
                    }).append(
                        window.$('<a/>', {
                            'class': 'label-link',
                            'href': '/admin/assets/edit/' + assetId + '-' + (assetDetails.filename || '').replace(/\.[^/.]+$/, "")
                        }).append(
                            window.$('<span/>', {
                                text: assetDetails.title || assetDetails.filename
                            })
                        )
                    )
                )
            ).append(
                window.$('<button/>', {
                    'class': 'delete icon',
                    'type': 'button',
                    'title': 'Remove',
                    'aria-label': 'Remove'
                })
            ).append(
                window.$('<input/>', {
                    'type': 'hidden',
                    'name': inputName,
                    'value': assetId
                })
            )
        );

        // Add the new asset to the list
        window.$(elementsContainer).append(newAssetLi);

        // Update Craft's ElementSelectInput instance if present
        const $elementsContainer = window.$(elementsContainer);
        const elementSelectInput = $elementsContainer.data('elementSelect');
        if (elementSelectInput) {
            elementSelectInput.resetElements();
        }

        // Add delete button functionality
        newAssetLi.find('.delete').on('click', function (e) {
            e.preventDefault();
            newAssetLi.remove();
            // Optionally, trigger a change event on the field
            const hiddenInput = newAssetLi.find('input[type="hidden"]')[0];
            if (hiddenInput) {
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // Trigger change event on the new hidden input
        const hiddenInput = newAssetLi.find('input[type="hidden"]')[0];
        if (hiddenInput) {
            console.log('Change hiddenInput');
            console.log(hiddenInput);
            hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Debug: log all asset field inputs and their names/values
        const allInputs = document.querySelectorAll('input[type="hidden"][name^="fields["]');
        allInputs.forEach(input => {
            console.log('Asset field input:', input.name, input.value);
        });
    }
}

// Global clean-up: Remove all empty asset field hidden inputs before submitting any form
// This ensures no empty asset field inputs are submitted, even if rendered by Craft

// document.addEventListener('submit', function (e) {
//     document.querySelectorAll('input[type="hidden"][name^="fields["]').forEach(function (input) {
//         if (!input.value) {
//             input.parentNode.removeChild(input);
//         }
//     });
// }, true);

// Global force: On submit, reconstruct all asset field hidden inputs based on visible chips
// This ensures the correct asset IDs are always submitted for every field

// document.addEventListener('submit', function (e) {
//     document.querySelectorAll('.field').forEach(function (fieldContainer) {
//         const elementsContainer = fieldContainer.querySelector('.elements');
//         if (!elementsContainer) return;

//         // Get all asset chips in this field
//         const chips = elementsContainer.querySelectorAll('.chip[data-id]');
//         // Remove all hidden inputs for this field
//         elementsContainer.querySelectorAll('input[type="hidden"][name^="fields["]').forEach(function (input) {
//             input.parentNode.removeChild(input);
//         });

//         // For each chip, add a hidden input with the correct name and value
//         chips.forEach(function (chip) {
//             const assetId = chip.getAttribute('data-id');
//             if (!assetId) return;
//             // Try to get the input name from a data attribute or fallback to the field handle
//             let inputName = fieldContainer.querySelector('input[type="hidden"][name^="fields["]')?.name;
//             if (!inputName) {
//                 // Fallback: try to get the field handle from the chip or field container
//                 const fieldHandle = chip.closest('.field')?.querySelector('.mediaflow-image-select')?.dataset.fieldHandle;
//                 const isSingle = chip.closest('.field')?.querySelector('.mediaflow-image-select')?.dataset.maxRelations === '1';
//                 inputName = isSingle ? `fields[${fieldHandle}]` : `fields[${fieldHandle}][]`;
//             }
//             // Add the hidden input
//             const input = document.createElement('input');
//             input.type = 'hidden';
//             input.name = inputName;
//             input.value = assetId;
//             elementsContainer.appendChild(input);
//         });
//     });
// }, true); 