import { ref, unref, nextTick, onBeforeUnmount } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

const DEFAULT_FIELD_MAP = {
    addressLine: 'address_line',
    street: 'street',
    buildingNumber: 'building_number',
    postalCode: 'postal_code',
    district: 'district',
    city: 'city',
    region: 'region',
    country: 'country',
    lat: 'lat',
    lng: 'lng',
};

/**
 * Reusable interactive Leaflet map & OpenStreetMap Nominatim national-address auto-filler.
 */
export function useNationalAddressMap({
    form,
    target = null,
    fieldMap = {},
    defaultLat = 24.7136,
    defaultLng = 46.6753,
    locale = null,
    onReady = null,
} = {}) {
    const keys = { ...DEFAULT_FIELD_MAP, ...fieldMap };
    const mapContainer = ref(null);
    const mapReady = ref(false);
    const geocodingLoading = ref(false);
    const isLocating = ref(false);

    let map = null;
    let marker = null;
    let geocodeController = null;

    const getTarget = () => {
        if (typeof target === 'function') return target();
        if (target) return unref(target);
        return unref(form);
    };

    const setFieldIfPresent = (obj, key, value) => {
        if (obj && key && key in obj) {
            obj[key] = value;
        }
    };

    const toEnglishDigits = (str) => {
        if (!str) return '';
        return String(str)
            .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
            .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
    };

    const abortPendingGeocode = () => {
        if (geocodeController) {
            geocodeController.abort();
            geocodeController = null;
        }
    };

    const reverseGeocode = async (lat, lng, isInit = false) => {
        abortPendingGeocode();
        geocodeController = new AbortController();
        geocodingLoading.value = true;

        try {
            const lang = unref(locale) === 'en' ? 'en' : 'ar';
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
                {
                    signal: geocodeController.signal,
                    headers: {
                        'User-Agent': 'KhidmhPro-App/1.0',
                    },
                }
            );

            if (!response.ok) throw new Error('Geocoding failed');
            const data = await response.json();
            const obj = getTarget();

            if (data?.address && obj) {
                const addr = data.address;
                setFieldIfPresent(obj, keys.country, addr.country || obj[keys.country] || '');
                setFieldIfPresent(
                    obj,
                    keys.region,
                    addr.state || addr.region || obj[keys.region] || ''
                );
                setFieldIfPresent(
                    obj,
                    keys.city,
                    addr.city ||
                        addr.town ||
                        addr.village ||
                        addr.municipality ||
                        obj[keys.city] ||
                        ''
                );
                setFieldIfPresent(
                    obj,
                    keys.district,
                    addr.suburb ||
                        addr.neighbourhood ||
                        addr.quarter ||
                        addr.district ||
                        obj[keys.district] ||
                        ''
                );

                const cleanDisplayName = toEnglishDigits(data.display_name || '');
                const numbers = cleanDisplayName.match(/\d+/g) || [];

                let bldNum = toEnglishDigits(addr.house_number || '').replace(/\D/g, '');
                if (bldNum.length !== 4) bldNum = numbers.find((n) => n.length === 4) || '';
                setFieldIfPresent(
                    obj,
                    keys.buildingNumber,
                    bldNum || obj[keys.buildingNumber] || ''
                );

                let postCode = toEnglishDigits(addr.postcode || '').replace(/\D/g, '');
                if (postCode.length !== 5) postCode = numbers.find((n) => n.length === 5) || '';
                setFieldIfPresent(obj, keys.postalCode, postCode || obj[keys.postalCode] || '');

                const addressParts = [];
                if (addr.road) addressParts.push(addr.road);
                if (addr.neighbourhood && addr.neighbourhood !== obj[keys.district]) {
                    addressParts.push(addr.neighbourhood);
                }

                const formattedAddress =
                    addressParts.length > 0
                        ? addressParts.join('، ')
                        : data.display_name?.split(',').slice(0, 2).join('،') || '';
                setFieldIfPresent(obj, keys.addressLine, formattedAddress);
                setFieldIfPresent(
                    obj,
                    keys.street,
                    addr.road || addr.pedestrian || addr.street || obj[keys.street] || ''
                );
            }
        } catch (error) {
            if (error?.name !== 'AbortError') {
                console.warn('[useNationalAddressMap] Reverse geocoding failed:', error);
            }
        } finally {
            geocodingLoading.value = false;
            if (isInit) {
                isLocating.value = false;
                nextTick(() => onReady?.());
            }
        }
    };

    const bindMarkerDrag = (m) => {
        m.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            setMarker(pos.lat, pos.lng);
        });
    };

    const setMarker = (lat, lng, isInit = false) => {
        const obj = getTarget();
        const fixedLat = parseFloat(Number(lat).toFixed(7));
        const fixedLng = parseFloat(Number(lng).toFixed(7));

        setFieldIfPresent(obj, keys.lat, fixedLat);
        setFieldIfPresent(obj, keys.lng, fixedLng);

        if (marker) {
            marker.setLatLng([fixedLat, fixedLng]);
        } else if (map) {
            marker = L.marker([fixedLat, fixedLng], { draggable: true }).addTo(map);
            bindMarkerDrag(marker);
        }

        if (map) map.panTo([fixedLat, fixedLng]);
        reverseGeocode(fixedLat, fixedLng, isInit);
    };

    const initMap = () => {
        if (!mapContainer.value || map) return;

        const obj = getTarget();
        const startLat = Number(obj?.[keys.lat] || unref(defaultLat) || 24.7136);
        const startLng = Number(obj?.[keys.lng] || unref(defaultLng) || 46.6753);

        map = L.map(mapContainer.value).setView([startLat, startLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        if (obj?.[keys.lat] && obj?.[keys.lng]) {
            marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
            bindMarkerDrag(marker);
        }

        map.on('click', (e) => setMarker(e.latlng.lat, e.latlng.lng));
        mapReady.value = true;
    };

    const mountWithRetry = (attempts = 0) => {
        if (mapContainer.value) {
            initMap();
            setTimeout(() => {
                if (map) map.invalidateSize();
                if (!isLocating.value) {
                    onReady?.();
                }
            }, 100);
        } else if (attempts < 10) {
            setTimeout(() => mountWithRetry(attempts + 1), 100);
        }
    };

    const fetchLocation = () => {
        isLocating.value = true;
        const finishNoLoc = () => {
            isLocating.value = false;
            nextTick(() => onReady?.());
        };

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const { latitude, longitude } = position.coords;
                    if (map) {
                        setMarker(latitude, longitude, true);
                    } else {
                        const obj = getTarget();
                        const fixedLat = parseFloat(Number(latitude).toFixed(7));
                        const fixedLng = parseFloat(Number(longitude).toFixed(7));
                        setFieldIfPresent(obj, keys.lat, fixedLat);
                        setFieldIfPresent(obj, keys.lng, fixedLng);
                        reverseGeocode(fixedLat, fixedLng, true);
                    }
                },
                finishNoLoc,
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 }
            );
        } else {
            finishNoLoc();
        }
    };

    const clearAddressFields = () => {
        const obj = getTarget();
        setFieldIfPresent(obj, keys.addressLine, '');
        setFieldIfPresent(obj, keys.buildingNumber, '');
        setFieldIfPresent(obj, keys.postalCode, '');
        setFieldIfPresent(obj, keys.district, '');
        setFieldIfPresent(obj, keys.city, '');
        setFieldIfPresent(obj, keys.region, '');
        setFieldIfPresent(obj, keys.country, '');
        setFieldIfPresent(obj, keys.lat, null);
        setFieldIfPresent(obj, keys.lng, null);

        if (marker && map) {
            map.removeLayer(marker);
            marker = null;
        }
    };

    const destroyMap = () => {
        abortPendingGeocode();
        if (map) {
            map.off();
            map.remove();
            map = null;
            marker = null;
            mapReady.value = false;
        }
    };

    onBeforeUnmount(() => {
        destroyMap();
    });

    return {
        mapContainer,
        mapReady,
        geocodingLoading,
        isLocating,
        initMap,
        mountWithRetry,
        fetchLocation,
        clearAddressFields,
        destroyMap,
    };
}
