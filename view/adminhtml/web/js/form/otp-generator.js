define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function (Component, $, $t, confirm) {
    'use strict';

    return Component.extend({
        defaults: {
            generateUrl: '',
            entityId: null,
            otp: null,
            otpExpiresAt: null,
            hasActiveOtp: false,
            hasToken: false,
            isNewClient: true
        },

        initialize: function () {
            this._super();
            this.updateState();
            return this;
        },

        initObservable: function () {
            this._super().observe([
                'otp',
                'otpExpiresAt',
                'hasActiveOtp',
                'hasToken',
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
                this.hasToken(!!(data.token_hash));
                this.hasActiveOtp(!!(data.otp_hash && data.otp_expires_at));
                if (data.otp_expires_at) {
                    this.otpExpiresAt(data.otp_expires_at);
                }
            }
        },

        generateOtp: function () {
            var self = this;

            if (!this.entityId) {
                return;
            }

            var message = $t('Generating a new OTP will revoke any existing access token for this client. The client will need to re-authorize using the new OTP.');

            if (this.hasToken()) {
                message = $t('WARNING: This client has an active token. Generating a new OTP will immediately revoke it. The AI client will lose access until re-authorized. Continue?');
            }

            confirm({
                title: $t('Generate New OTP'),
                content: message,
                actions: {
                    confirm: function () {
                        self.doGenerateOtp();
                    }
                }
            });
        },

        doGenerateOtp: function () {
            var self = this;

            $.ajax({
                url: this.generateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    entity_id: this.entityId,
                    form_key: window.FORM_KEY
                },
                showLoader: true,
                success: function (response) {
                    if (response.success) {
                        self.otp(response.otp);
                        self.otpExpiresAt(response.expires_at);
                        self.hasActiveOtp(true);
                        self.hasToken(false);
                    } else {
                        alert(response.message || $t('Failed to generate OTP'));
                    }
                },
                error: function () {
                    alert($t('An error occurred while generating OTP'));
                }
            });
        },

        copyOtp: function () {
            var otp = this.otp();
            if (otp && navigator.clipboard) {
                navigator.clipboard.writeText(otp).then(function () {
                    alert($t('OTP copied to clipboard'));
                });
            }
        }
    });
});