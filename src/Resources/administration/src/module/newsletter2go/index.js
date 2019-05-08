import { Module } from 'src/core/shopware';
import newsletter2goIndex from './page/newsletter2go-index';
import './extension/sw-settings-index';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Module.register('newsletter2go-app', {
    type: 'plugin',
    name: 'Newsletter2go',
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
            component: newsletter2goIndex,
            path: 'index',
            meta: {
                parentPath: 'sw.settings.index'
            }
        }
    }
});
