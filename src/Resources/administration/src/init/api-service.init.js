import { Application } from 'src/core/shopware';
import ConversionTrackingService
    from '../../src/core/service/api/conversion-tracking.service';
import ConnectionService
    from '../../src/core/service/api/connection.service';


Application.addServiceProvider('ConversionTrackingService', (container) => {
    const initContainer = Application.getContainer('init');

    return new ConversionTrackingService(initContainer.httpClient, container.loginService);
});

Application.addServiceProvider('ConnectionService', (container) => {
    const initContainer = Application.getContainer('init');

    return new ConnectionService(initContainer.httpClient, container.loginService);
});
