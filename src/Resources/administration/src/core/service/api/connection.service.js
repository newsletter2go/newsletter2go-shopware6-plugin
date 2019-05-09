import ApiService from 'src/core/service/api.service';

class ConnectionService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'n2g') {
        super(httpClient, loginService, apiEndpoint);
    }

    testConnection() {
        const apiRoute = `${this.getApiBasePath()}/connection`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getIntegrationLink() {
        const apiRoute = `${this.getApiBasePath()}/integration`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders()
            }
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default ConnectionService;
