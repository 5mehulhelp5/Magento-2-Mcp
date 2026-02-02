define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function (Component, $, $t, confirm) {
    'use strict';

    return Component.extend({
        defaults: {
            generateTokenUrl: '',
            entityId: null,
            hasToken: false,
            isNewClient: true,
            generatedToken: null
        },

        initialize: function () {
            this._super();
            this.updateState();
            return this;
        },

        initObservable: function () {
            this._super().observe([
                'hasToken',
                'isNewClient',
                'generatedToken'
            ]);
            return this;
        },

        updateState: function () {
            var source = this.source;
            if (source && source.data) {
                var data = source.data;
                this.entityId = data.entity_id || null;
                this.isNewClient(!this.entityId);
                this.hasToken(!!(data.token_hash));
            }
        },

        generateToken: function () {
            var self = this;

            if (!this.entityId) {
                return;
            }

            var message = $t('Generating a new token will replace any existing token. The new token will be shown only once.');

            if (this.hasToken()) {
                message = $t('WARNING: This client already has an active token. Generating a new token will immediately replace it. Continue?');
            }

            confirm({
                title: $t('Generate Access Token'),
                content: message,
                actions: {
                    confirm: function () {
                        self.doGenerateToken();
                    }
                }
            });
        },

        doGenerateToken: function () {
            var self = this;

            $.ajax({
                url: this.generateTokenUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    entity_id: this.entityId,
                    form_key: window.FORM_KEY
                },
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        self.generatedToken(response.token);
                        self.hasToken(true);
                    } else {
                        alert(response.message || $t('Failed to generate token'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while generating token'));
                }
            });
        },

        copyToken: function () {
            var token = this.generatedToken();
            if (token && navigator.clipboard) {
                navigator.clipboard.writeText(token).then(function () {
                    alert($t('Token copied to clipboard'));
                });
            }
        }
    });
});