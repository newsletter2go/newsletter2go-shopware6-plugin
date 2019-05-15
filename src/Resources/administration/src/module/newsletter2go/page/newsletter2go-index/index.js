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
            companyId: '',
            isConnected: false,
            isLoading: true,
            isAccountConfigLoading: true,
            isDisconnectDialogVisible: false,
            displayConnectButton: false
        };
    },

    created() {
        this.testConnection();
        this.createdComponent();
    },
    computed: {},

    methods: {
        createdComponent() {
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
            this.ConnectionService.testConnection().then((response) => {
                if (response.status === 200) {
                    this.isConnected = true;
                    this.companyId = response.company_id;
                } else {
                    this.isConnected = false;
                    this.displayConnectButton = true;

                    if (response.status !== 203) {
                        this.createNotificationError({
                            title: this.$tc('newsletter2go.settingForm.titleSaveError'),
                            message: `Status ${response.status}: ${response.error}`
                        });
                    }
                }
                this.isLoading = false;
            });
        },

        viewDisconnectDialog() {
            console.log('clicked');
            this.isDisconnectDialogVisible = true;
        },

        closeDisconnectDialog() {
            this.isDisconnectDialogVisible = false;
        },

        disconnect() {
            this.isLoading = true;
            this.ConnectionService.disconnect().then((response) => {
                if (response.status === 200) {
                    this.isConnected = false;
                }
                this.isLoading = false;
                this.isDisconnectDialogVisible = false;
            });
        }
    }
};
