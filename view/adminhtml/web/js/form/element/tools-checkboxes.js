define([
    'underscore',
    'uiElement'
], function (_, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            template: 'Freento_Mcp/form/element/tools-checkboxes',
            groupedOptions: [],
            options: [],
            value: [],
            visible: true,
            label: '',
            links: {
                value: '${ $.provider }:data.tools'
            }
        },

        /**
         * @returns {Object}
         */
        initObservable: function () {
            this._super();
            this.observe(['groupedOptions', 'value', 'visible']);
            this.groupOptions();

            return this;
        },

        /**
         * Group tools by module
         */
        groupOptions: function () {
            var grouped = {},
                result = [];

            _.each(this.options, function (option) {
                var module = option.module || 'Other';
                if (!grouped[module]) {
                    grouped[module] = [];
                }
                grouped[module].push(option);
            });

            _.each(Object.keys(grouped).sort(), function (module) {
                result.push({
                    module: module,
                    tools: grouped[module]
                });
            });

            this.groupedOptions(result);
        },

        /**
         * @param {String} toolValue
         * @returns {Boolean}
         */
        isChecked: function (toolValue) {
            return _.contains(this.value() || [], toolValue);
        },

        /**
         * @param {String} toolValue
         */
        toggleValue: function (toolValue) {
            var values = (this.value() || []).slice();
            var idx = values.indexOf(toolValue);

            if (idx === -1) {
                values.push(toolValue);
            } else {
                values.splice(idx, 1);
            }

            this.value(values);
        },

        /**
         * @param {String} module
         */
        toggleModule: function (module) {
            var self = this,
                values = (this.value() || []).slice(),
                group = _.find(this.groupedOptions(), function (g) {
                    return g.module === module;
                }),
                toolValues = _.pluck(group.tools, 'value'),
                allChecked = _.every(toolValues, function (v) {
                    return self.isChecked(v);
                });

            if (allChecked) {
                values = _.difference(values, toolValues);
            } else {
                values = _.union(values, toolValues);
            }

            this.value(values);
        },

        /**
         * @param {String} module
         * @returns {Boolean}
         */
        isModuleChecked: function (module) {
            var self = this,
                group = _.find(this.groupedOptions(), function (g) {
                    return g.module === module;
                });

            if (!group) {
                return false;
            }

            return _.every(group.tools, function (tool) {
                return self.isChecked(tool.value);
            });
        }
    });
});