const OSRM_BASE_URL = 'https://router.project-osrm.org/route/v1/driving';

export class OsrmRouter {
    /**
     * Fetch a route between two or more coordinates following the road network.
     * @param {Array<[number, number]>} coordinates - Array of [lng, lat] pairs
     * @returns {Promise<Array<[number, number]>>} - Coordinates of the route following roads
     */
    async getRoute(coordinates) {
        if (coordinates.length < 2) {
            return coordinates;
        }

        const coords = coordinates.map((c) => c.join(',')).join(';');
        const url = `${OSRM_BASE_URL}/${coords}?overview=full&geometries=geojson`;

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`OSRM error: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.code !== 'Ok' || !data.routes || data.routes.length === 0) {
            throw new Error('No route found');
        }

        return data.routes[0].geometry.coordinates;
    }

    /**
     * Fetch a route segment between the last two waypoints only.
     * @param {Array<[number, number]>} from - [lng, lat]
     * @param {Array<[number, number]>} to - [lng, lat]
     * @returns {Promise<Array<[number, number]>>} - Coordinates of the segment following roads
     */
    async getSegment(from, to) {
        return this.getRoute([from, to]);
    }
}
