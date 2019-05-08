import { Mixin } from 'src/core/shopware';
import template from './newsletter2go-index.html.twig';

export default {
    name: 'newsletter2go-index',

    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            setting: {},
            isLoading: true,
            landingPageTypes: ['Login', 'Billing'],
            intents: ['sale', 'authorize', 'order']
        };
    },

    created() {
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
            console.log('createdComponent');
        },

        onSave() {
            this.isLoading = true;
            console.log('onSave');
            this.isLoading = false;
        },

        onTest() {
            this.isLoading = true;
            console.log('loading ...');
            this.isLoading = false;
        }
    }
};
