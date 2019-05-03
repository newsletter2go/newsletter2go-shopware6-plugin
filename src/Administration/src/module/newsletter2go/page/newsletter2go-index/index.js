import { Mixin } from 'src/core/shopware';
import template from './newsletter2go-index.html.twig';

export default {
    name: 'newsletter2go-index',

    template,

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        console.log('data');

        return {
            setting: {},
            isLoading: true,
            landingPageTypes: ['Login', 'Billing'],
            intents: ['sale', 'authorize', 'order']
        };
    },

    created() {
        console.log('created');
        this.createdComponent();
    },

    computed: {
        settingStore() {
            console.log('settingStore');
        },
        clientCredentialsFilled() {
            console.log('clientCredentialsFilled');
        }
    },

    methods: {
        createdComponent() {
            console.log('createdComponent');
        },

        onSave() {
            console.log('onSave');
        },

        onTest() {
            console.log('onTest');
        }
    }
};
