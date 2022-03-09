const ApiService = Shopware.Classes.ApiService;

class ConversionTrackingService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'n2g') {
        super(httpClient, loginService, apiEndpoint);
    }

    getValue() {
        const apiRoute = `${this.getApiBasePath()}/tracking`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    updateValue(value) {
        const apiRoute = `${this.getApiBasePath()}/tracking`;

        return this.httpClient.put(
            apiRoute,
            {
                conversion_tracking: value
            },
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default ConversionTrackingService;
