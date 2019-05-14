import { Mixin } from 'src/core/shopware';
import template from './newsletter2go-index.html.twig';

export default {
    name: 'newsletter2go-index',

    template,

    inject: ['ConversionTrackingService', 'ConnectionService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            setting: {},
            isConnected: true,
            isLoading: true,
            isAccountConfigLoading: true
        };
    },

    created() {
        this.testConnection();
        this.createdComponent();
    },

    computed: {
        settingStore() {
            return null;
        },
        clientCredentialsFilled() {
            return null;
        }
    },

    methods: {
        createdComponent() {
            this.isLoading = true;

            this.ConnectionService.getIntegrationLink().then((response) => {
                this.setting.connectLink = response.integration;
            });

            this.ConversionTrackingService.getValue().then((response) => {
                this.setting.conversionTracking = response.conversion_tracking;
                if (response.error !== 'undefined') {
                    this.createNotificationSuccess({
                        title: this.$tc('newsletter2go.settingForm.titleSaveSuccess'),
                        message: response.error
                    });
                }

                this.isLoading = false;
            });
        },

        onSave() {
            this.isLoading = true;
            this.ConversionTrackingService.updateValue(this.setting.conversionTracking).then((response) => {
                let title = '';
                let message = '';

                if (response.error !== 'undefined') {
                    title = this.$tc('newsletter2go.settingForm.titleSaveError');
                    message = response.error;
                } else {
                    title = this.$tc('newsletter2go.settingForm.titleSaveSuccess');
                    message = this.$tc('newsletter2go.settingForm.messageWebhookUpdated');
                }

                this.createNotificationSuccess({
                    title: title,
                    message: message
                });

                this.isLoading = false;
            });
        },

        testConnection() {
            this.isAccountConfigLoading = true;
            this.ConnectionService.testConnection().then((response) => {
                if (response.status === 200) {
                    this.setting.connectionIcon = 'default-basic-checkmark-circle';
                    this.setting.connectionIconColor = '#65c765';
                    this.setting.connectionText = this.$tc('newsletter2go.settingForm.messageConnectedSuccess') + response.company_id;
                } else {
                    this.isConnected = false;
                    this.setting.connectionIcon = 'default-badge-error';
                    this.setting.connectionIconColor = '#f76363';
                    this.setting.connectionText = this.$tc('newsletter2go.settingForm.messageConnectedError');
                    if (response.status !== 203) {
                        this.createNotificationError({
                            title: this.$tc('newsletter2go.settingForm.titleSaveError'),
                            message: `Status ${response.status}: ${response.error}`
                        });
                    }
                }
                this.isAccountConfigLoading = false;
            });
        }
    }
};
