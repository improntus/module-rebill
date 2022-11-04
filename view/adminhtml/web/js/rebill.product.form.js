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
                title: 'Edit Frequencies',
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

            document.addEventListener("click", e => {
                if (e.target.matches(".action-close")) {
                    self.removeValidateClass();
                }
            });

        },
        saveFrequencies: function () {
            let frequencies = [];
            let errorMsg = "";
            let self = this;
            $('#rebill-frequency-modal table tbody tr').each(function () {
                let id = parseInt($(this).find('[data-type="id"]').text());
                let frequencyElemt = $(this).find('[data-type="frequency"]');
                let frequencyTypeElemt = $(this).find('[data-type="frequency-type"]');
                let recurringPaymentsElemt = $(this).find('[data-type="max-recurring-payments"]');
                let priceElemt = $(this).find('[data-type="price"]');
                let initialCostElemt = $(this).find('[data-type="initial-cost"]');

                let frequency = parseInt(frequencyElemt.val());
                let frequencyType = frequencyTypeElemt.val();
                let recurringPayments = parseInt(recurringPaymentsElemt.val());
                let price = parseInt(priceElemt.val());
                let initialCost = parseInt(initialCostElemt.val());

                errorMsg += self.getErrorMsg(frequencyElemt, recurringPaymentsElemt, frequency, recurringPayments)

                let frequencyObj = {
                    id: id,
                    frequency: frequency,
                    frequencyType: frequencyType,
                    recurringPayments: recurringPayments,
                    price: price,
                    initialCost: initialCost,
                }

                if (frequencies.some(x => x.frequency === frequencyObj.frequency &&
                    x.frequencyType === frequencyType &&
                    x.recurringPayments === recurringPayments &&
                    x.price === price &&
                    x.initialCost === initialCost
                )) {
                    $(this).addClass("rebill-invalid");
                    errorMsg += "There are duplicate rows."
                }

                frequencies.push(frequencyObj);
            });
            if (errorMsg === "") {
                $('[name="product[rebill_frequency]"]').val(JSON.stringify(frequencies)).change();
            } else {
                window.alert(errorMsg);
            }
            return errorMsg !== "";
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
            let maxRecurringPaymentsTooltip = $t('If it is 0 the subscription will be cyclical');
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
        getErrorMsg: function (frequencyElemt, recurringPaymentsElemt, frequency, recurringPayments) {

            let msg = "";
            if (frequency <= 0) {
                frequencyElemt.addClass("rebill-invalid");
                msg += $t('The frequency should be bigger than 0.')
            }

            if (recurringPayments === 1 || recurringPayments < 0) {
                recurringPaymentsElemt.addClass("rebill-invalid");
                msg += (msg === "" ? "" : `\n`) + $t('The Max Recurring Payments should be bigger equal to 0 or bigger than 1.')
            }

            if(isNaN(recurringPayments) || recurringPaymentsElemt.val().includes(".")){
                recurringPaymentsElemt.addClass("rebill-invalid");
                msg += `\n` + $t('The Max Recurring Payments should be a number integer.');
            }

            return msg;
        }
    });

    return $.mage.rebill_product_form;
});
