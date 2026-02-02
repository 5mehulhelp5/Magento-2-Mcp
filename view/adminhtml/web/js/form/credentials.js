define([
    'uiComponent',
    'jquery',
    'mage/translate'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            getSecretUrl: '',
            entityId: null,
            clientId: null,
            createdAt: null,
            isNewClient: true
        },

        initialize: function () {
            this._super();
            this.updateState();
            return this;
        },

        initObservable: function () {
            this._super().observe([
                'clientId',
                'createdAt',
                'isNewClient'
            ]);
            return this;
        },

        updateState: function () {
            var source = this.source;
            if (source && source.data) {
                var data = source.data;
                this.entityId = data.entity_id || null;
                this.isNewClient(!this.entityId);
                this.clientId(data.client_id || '');
                this.createdAt(data.created_at || '');
            }
        },

        copyClientId: function () {
            var clientId = this.clientId();
            if (clientId && navigator.clipboard) {
                navigator.clipboard.writeText(clientId).then(function () {
                    alert($t('Client ID copied to clipboard'));
                });
            }
        },

        copySecret: function () {
            var self = this;

            if (!this.entityId) {
                return;
            }

            $.ajax({
                url: this.getSecretUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    entity_id: this.entityId,
                    form_key: window.FORM_KEY
                },
                showLoader: true,
                success: function (response) {
                    if (response.success && response.secret) {
                        if (navigator.clipboard) {
                            navigator.clipboard.writeText(response.secret).then(function () {
                                alert($t('Client Secret copied to clipboard'));
                            });
                        }
                    } else {
                        alert(response.message || $t('Failed to get secret'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while getting secret'));
                }
            });
        }
    });
});