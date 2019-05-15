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
            setting: {
                connectionMessage: '',
                connectionIconName: '',
                connectionIconColor: ''
            },
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
            if (this.isConnected) {
                this.isLoading = true;

                this.ConversionTrackingService.updateValue(this.setting.conversionTracking).then((response) => {
                    let title = '';
                    let message = '';

                    if (response.error !== 'undefined') {
                        title = this.$tc('newsletter2go.settingForm.titleSaveError');
                        message = response.error;
                    } else {
                        title = this.$tc('newsletter2go.settingForm.titleSaveSuccess');
                        message = this.$tc('newsletter2go.settingForm.messageSaveSuccess');
                    }

                    this.createNotificationSuccess({
                        title: title,
                        message: message
                    });

                    this.isLoading = false;
                });
            }
        },

        testConnection() {
            this.ConnectionService.testConnection().then((response) => {
                if (response.status === 200) {
                    this.isConnected = true;
                    this.companyId = response.company_id;
                    this._setConnectionViewSuccessful();
                } else {
                    this.isConnected = false;
                    this.displayConnectButton = true;
                    this._setConnectionViewNotConnected();

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

        _setConnectionViewSuccessful() {
            this.setting.connectionIconName = 'default-basic-checkmark-circle';
            this.setting.connectionIconColor = '#65c765';
            this.setting.connectionMessage = this.$tc('newsletter2go.settingForm.messageConnectedSuccess') + this.companyId;
        },

        _setConnectionViewNotConnected() {
            this.setting.connectionIconName = 'default-badge-error';
            this.setting.connectionIconColor = '#f76363';
            this.setting.connectionMessage = this.$tc('newsletter2go.settingForm.messageConnectedError');
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
            this.isConnected = false;
            this.isDisconnectDialogVisible = false;
            this.displayConnectButton = true;
            this._setConnectionViewNotConnected();
            this.ConnectionService.disconnect().then((response) => {
                if (response.status !== 200) {
                    this.createNotificationSuccess({
                        title: `Code ${response.status}`,
                        message: this.$tc('newsletter2go.settingForm.titleSaveError')
                    });
                }

                this.isLoading = false;
            });
        }
    }
};
