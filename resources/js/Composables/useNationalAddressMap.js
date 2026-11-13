import { ref, nextTick } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

/**
 * Reusable interactive Leaflet map & OpenStreetMap Nominatim national-address auto-filler.
 */
export function useNationalAddressMap({ form, defaultLat, defaultLng, locale, onReady }) {
    const mapContainer = ref(null);
    const mapReady = ref(false);
    const geocodingLoading = ref(false);
    const isLocating = ref(false);

    let map = null;
    let marker = null;

    const toEnglishDigits = (str) => {
        if (!str) return '';
        return String(str)
            .replace(/[٠-٩]/g, (d) => '٠١٢٣٤٥٦٧٨٩'.indexOf(d))
            .replace(/[۰-۹]/g, (d) => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d));
    };

    const reverseGeocode = async (lat, lng, isInit = false) => {
        if (geocodingLoading.value) return;
        geocodingLoading.value = true;

        try {
            const lang = locale?.value === 'ar' ? 'ar' : 'en';
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&addressdetails=1&accept-language=${lang}`,
                {
                    headers: {
                        'User-Agent': 'KhidmhPro-App/1.0',
                    },
                }
            );

            if (!response.ok) throw new Error('Geocoding failed');
            const data = await response.json();

            if (data?.address) {
                const addr = data.address;
                form.country = addr.country || form.country || '';
                form.region = addr.state || addr.region || form.region || '';
                form.city =
                    addr.city || addr.town || addr.village || addr.municipality || form.city || '';
                form.district =
                    addr.suburb ||
                    addr.neighbourhood ||
                    addr.quarter ||
                    addr.district ||
                    form.district ||
                    '';

                const cleanDisplayName = toEnglishDigits(data.display_name || '');
                const numbers = cleanDisplayName.match(/\d+/g) || [];

                let bldNum = toEnglishDigits(addr.house_number || '').replace(/\D/g, '');
                if (bldNum.length !== 4) bldNum = numbers.find((n) => n.length === 4) || '';
                form.building_number = bldNum || form.building_number || '';

                let postCode = toEnglishDigits(addr.postcode || '').replace(/\D/g, '');
                if (postCode.length !== 5) postCode = numbers.find((n) => n.length === 5) || '';
                form.postal_code = postCode || form.postal_code || '';

                const addressParts = [];
                if (addr.road) addressParts.push(addr.road);
                if (addr.neighbourhood && addr.neighbourhood !== form.district) {
                    addressParts.push(addr.neighbourhood);
                }

                form.address_line =
                    addressParts.length > 0
                        ? addressParts.join('، ')
                        : data.display_name?.split(',').slice(0, 2).join('،') || '';
            }
        } catch (error) {
            console.warn('[useNationalAddressMap] Reverse geocoding failed:', error);
        } finally {
            geocodingLoading.value = false;
            if (isInit) {
                isLocating.value = false;
                nextTick(() => onReady?.());
            }
        }
    };

    const setMarker = (lat, lng, isInit = false) => {
        if ('lat' in form) form.lat = parseFloat(lat.toFixed(7));
        if ('lng' in form) form.lng = parseFloat(lng.toFixed(7));

        if (marker) {
            marker.setLatLng([lat, lng]);
        } else if (map) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                setMarker(pos.lat, pos.lng);
            });
        }

        if (map) map.panTo([lat, lng]);
        reverseGeocode(parseFloat(lat.toFixed(7)), parseFloat(lng.toFixed(7)), isInit);
    };

    const initMap = () => {
        if (!mapContainer.value || map) return;

        const startLat = Number(form.lat || defaultLat.value || 24.7136);
        const startLng = Number(form.lng || defaultLng.value || 46.6753);

        map = L.map(mapContainer.value).setView([startLat, startLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        if (form.lat && form.lng) {
            marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
            marker.on('dragend', (e) => {
                const pos = e.target.getLatLng();
                setMarker(pos.lat, pos.lng);
            });
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
                    if (map) setMarker(latitude, longitude, true);
                    else reverseGeocode(latitude, longitude, true);
                },
                finishNoLoc,
                { enableHighAccuracy: false, timeout: 5000, maximumAge: 60000 }
            );
        } else {
            finishNoLoc();
        }
    };

    const clearAddressFields = () => {
        form.address_line = '';
        form.building_number = '';
        form.postal_code = '';
        form.district = '';
        form.city = '';
        form.region = '';
        form.country = '';
        if ('lat' in form) form.lat = null;
        if ('lng' in form) form.lng = null;

        if (marker && map) {
            map.removeLayer(marker);
            marker = null;
        }
    };

    const destroyMap = () => {
        if (map) {
            map.off();
            map.remove();
            map = null;
            marker = null;
            mapReady.value = false;
        }
    };

    return {
        mapContainer,
        mapReady,
        isLocating,
        mountWithRetry,
        fetchLocation,
        clearAddressFields,
        destroyMap,
    };
}
