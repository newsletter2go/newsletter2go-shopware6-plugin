import './page/newsletter2go-index';
import './extension/sw-settings-index';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('newsletter2go-app', {
    type: 'plugin',
    name: 'Newsletter2GoSW6',
    description: 'newsletter2go.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#9AA8B5',
    icon: 'default-action-settings',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        index: {
            component: 'newsletter2go-index',
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
