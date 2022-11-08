/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2022 Improntus (http://www.improntus.com/)
 * @package Improntus_Rebill
 */
define(['jquery', 'mage/translate', 'Magento_Ui/js/modal/modal'], function ($, $t, modal) {
    'use strict';
    $.widget('mage.rebill_product_form', {
        options: {
            attributes: [],
            product_type: 'simple',
            enabler_attribute_code: 'rebill_enable_subscription',
            product_price: 0,
            is_product_child: false
        },
        _create: function () {
            let self = this;
            $.each(self.options.attributes, function (index, attribute) {
                if (attribute['apply_to'].includes(self.options.product_type)) {
                    let interval = setInterval(function () {
                        let el = self.getAttributeElement(attribute['code']);
                        if (el.length) {
                            self.initAttribute(attribute);
                            clearInterval(interval);
                        }
                    }, 100);
                }
            });
        },
        initAttribute: function (attribute) {
            let self = this;
            if (attribute['code'] === 'rebill_frequency') {
                self.initFrequencyForm(attribute);
            }
            self.addTooltip(attribute);
        },
        initFrequencyForm: function (attribute) {
            let self = this;
            let frequencies = attribute['value'];
            let position = 1;
            let options = {
                type: 'slide',
                responsive: true,
                title: $.mage.__('Edit Frequencies'),
                buttons: [{
                    text: $.mage.__('Add Frequency'),
                    class: '',
                    click: function () {
                        let frequency = {
                            id: Math.floor(Date.now() / 1000) + position,
                            frequency: 1,
                            frequencyType: 'months',
                            recurringPayments: 0,
                            price: this.options.product_price,
                            initialCost: 0
                        };
                        position++;
                        self.addFrequencyRow(frequency, this);
                    }
                }, {
                    text: $.mage.__('Save'),
                    class: 'primary',
                    click: function () {
                        let thereAreError = self.saveFrequencies();
                        if (!thereAreError) {
                            self.removeValidateClass();
                            this.closeModal();
                        }

                    }
                }]
            };
            let buttonElement = $('<button type="button" class="button primary"></button>').text($t('Edit Frequencies'));
            let frequencyModal = modal(options, $('#rebill-frequency-modal'));
            $.each(frequencies, function (index, value) {
                self.addFrequencyRow(value, frequencyModal);
            });
            self.getAttributeElement(attribute['code']).css({
                visibility: 'hidden',
                height: '0',
                padding: '0',
                width: '0',
                margin: '0',
                display: 'block'
            });
            self.getAttributeElement(attribute['code']).parent().append(buttonElement);
            buttonElement.click(function () {
                frequencyModal.openModal();
            });

            const disabledTypes = [''];
            self.disableFrequencyButton('.admin__control-select',
                disabledTypes,
                buttonElement);

            document.addEventListener("click", e => {
                if (e.target.matches(".action-close")) {
                    self.removeValidateClass();
                }
            });
        },
        saveFrequencies: function () {
            let frequencies = [];
            let errorMsgArray = new Set();
            let self = this;
            $('#rebill-frequency-modal table tbody tr').each(function () {
                let elemt = self.getFrequencieElemt(this);

                let frequencyObj = {
                    id: parseInt(elemt.id.text()),
                    frequency: parseInt(elemt.frequency.val()),
                    frequencyType: elemt.frequencyType.val(),
                    recurringPayments: parseInt(elemt.recurringPayments.val()),
                    price: parseInt(elemt.price.val()),
                    initialCost: parseInt(elemt.initialCost.val()),
                }

                self.getErrorMsg(elemt, frequencyObj, errorMsgArray);

                if (frequencies.some(x => x.frequency === frequencyObj.frequency &&
                    x.frequencyType === frequencyObj.frequencyType &&
                    x.recurringPayments === frequencyObj.recurringPayments &&
                    x.price === frequencyObj.price &&
                    x.initialCost === frequencyObj.initialCost
                )) {
                    $(this).addClass("rebill-row-invalid");
                    errorMsgArray.add($t("There are duplicate rows."));
                }

                frequencies.push(frequencyObj);
            });
            if (errorMsgArray.size === 0) {
                $('[name="product[rebill_frequency]"]').val(JSON.stringify(frequencies)).change();
            } else {
                window.alert(([...errorMsgArray].join("\n")));
            }
            return errorMsgArray.size > 0;
        },
        addFrequencyRow: function (frequency, modal) {
            if (this.options.is_product_child) {
                modal.buttons[0].disable();
            }
            let price = frequency.price ?? this.options.product_price;
            let id = frequency.id;
            let idField = $(`<span data-id="${id}" data-type="id"></span>`).text(id);
            let frequencyField = $(`<input min="1" type="number" class="input-text" data-id="${id}" data-type="frequency" value="${frequency.frequency}" />`);
            let frequencyTypeField = $(`<select class="select" data-id="${id}" data-type="frequency-type"></select>`)
                .append($('<option value="months"></option>').text($t('Months')))
                .append($('<option value="years"></option>').text($t('Years')))
                .val(frequency.frequencyType ?? 'months').change();
            let maxRecurringPaymentsTooltip = $t('If it is 0 the subscription will be recurrent');
            let recurringPaymentsField = $(`<input min="0" type="number" class="input-text" data-id="${id}" data-type="max-recurring-payments" value="${frequency.recurringPayments}" />&nbsp;<span tooltip="${maxRecurringPaymentsTooltip}" flow="right">?</span>`);
            let priceField = $(`<input type="number" class="input-text" data-id="${id}" data-type="price" value="${price}" />`);
            let initialCostField = $(`<input min="0" type="number" class="input-text" data-id="${id}" data-type="initial-cost" value="${frequency.initialCost}" />`);
            let actions = $(`<button type="button" class="action button"></button>`).text($t('Delete'));
            $('#rebill-frequency-modal table tbody')
                .append($('<tr></tr>')
                    .append($('<td style="display: none"></td>').append(idField))
                    .append($('<td></td>').append(frequencyField))
                    .append($('<td></td>').append(frequencyTypeField))
                    .append($('<td></td>').append(recurringPaymentsField))
                    .append($('<td></td>').append(priceField))
                    .append($('<td></td>').append(initialCostField))
                    .append($('<td></td>').append(actions)));
            actions.click(function () {
                $(this).parent().parent().remove();
            });
        },
        addTooltip: function (attribute) {
            let self = this;
            let tooltipElement = $('<div class="rebill-tooltip"></div>'),
                tooltipButtonElement = $('<span class="rebill-tooltip-button"></span>'),
                tooltipTextWrapperElement = $('<div class="rebill-tooltip-text"></div>'),
                tooltipTextSpanElement = $('<span></span>').text(attribute['tooltip']);
            tooltipTextWrapperElement.append($('<span></span>').append(tooltipTextSpanElement));
            tooltipButtonElement.mouseenter(function () {
                $(this).parent().addClass('active');
            });
            tooltipButtonElement.mouseleave(function () {
                $(this).parent().removeClass('active');
            });
            tooltipElement.append(tooltipButtonElement);
            tooltipElement.append(tooltipTextWrapperElement);
            self.getAttributeElement(attribute['code']).parents('.admin__field').find('.admin__field-label').append(tooltipElement);
        },
        getAttributeElement: function (code) {
            return $(`[name='product[${code}]']`);
        },
        getAttributeValue: function (code) {
            return parseInt(this.getAttributeElement(code).val());
        },
        removeValidateClass: function () {
            $('#rebill-frequency-modal table tbody tr').each(function () {
                let frequencyElemt = $(this).find('[data-type="frequency"]');
                frequencyElemt.removeClass("rebill-invalid");
                let recurringPaymentsElemt = $(this).find('[data-type="max-recurring-payments"]');
                recurringPaymentsElemt.removeClass("rebill-invalid");
                $(this).removeClass("rebill-invalid");
            });
        },
        getErrorMsg: function (elemt, frequencyObj, lstErrorMsg) {

            let msg = "";
            if (frequencyObj.frequency <= 0) {
                elemt.frequency.addClass("rebill-invalid");
                lstErrorMsg.add($t('The frequency should be bigger than 0.'));
            }

            if (frequencyObj.recurringPayments === 1 || frequencyObj.recurringPayments < 0) {
                elemt.recurringPayments.addClass("rebill-invalid");
                lstErrorMsg.add($t('The Max Recurring Payments should be bigger equal to 0 or bigger than 1.'));
            }

            if (isNaN(frequencyObj.recurringPayments) || elemt.recurringPayments.val().includes(".")) {
                elemt.recurringPayments.addClass("rebill-invalid");
                lstErrorMsg.add($t('The Max Recurring Payments should be a number integer.'));
            }

            return msg;
        },
        getFrequencieElemt: function (trElem) {
            return {
                id: $(trElem).find('[data-type="id"]'),
                frequency: $(trElem).find('[data-type="frequency"]'),
                frequencyType: $(trElem).find('[data-type="frequency-type"]'),
                recurringPayments: $(trElem).find('[data-type="max-recurring-payments"]'),
                price: $(trElem).find('[data-type="price"]'),
                initialCost: $(trElem).find('[data-type="initial-cost"]'),
            }
        },
        disableFrequencyButton: function (elementId, typesDisabled, buttonElement) {
            $(elementId).change(function () {
                let select = this;
                if (typesDisabled.includes(select.options[select.selectedIndex].value)) {
                    buttonElement.prop('disabled', true);
                } else {
                    buttonElement.prop('disabled', false);
                }
            })
        }
    });

    return $.mage.rebill_product_form;
});
