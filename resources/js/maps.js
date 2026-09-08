import L from 'leaflet';

const iranCenter = [32.4, 53.7];
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const persianNumber = value => Number(value).toLocaleString('fa-IR', {maximumFractionDigits: 1});

function coordinates(latitude, longitude) {
    if (latitude === null || longitude === null || latitude === '' || longitude === '' || latitude === undefined || longitude === undefined) return null;
    const lat = Number(latitude);
    const lng = Number(longitude);
    return Number.isFinite(lat) && Number.isFinite(lng) && lat >= 24 && lat <= 41 && lng >= 43 && lng <= 64 ? [lat, lng] : null;
}

function createMap(element, center, onTileStatus) {
    const map = L.map(element, {
        center: center ?? iranCenter, zoom: center ? 12 : 5,
        zoomControl: false, scrollWheelZoom: false,
        zoomAnimation: !reducedMotion, fadeAnimation: !reducedMotion, markerZoomAnimation: !reducedMotion,
    });
    L.control.zoom({position: 'topleft', zoomInTitle: 'بزرگ‌نمایی', zoomOutTitle: 'کوچک‌نمایی'}).addTo(map);
    let failed = false;
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
    }).on('loading', () => { failed = false; })
        .on('tileerror', () => { failed = true; onTileStatus(true); })
        .on('load', () => onTileStatus(failed)).addTo(map);
    new ResizeObserver(() => {
        if (element.clientWidth && element.clientHeight) map.invalidateSize({animate: false});
    }).observe(element);
    return map;
}

function markerIcon(label = '●') {
    const content = document.createElement('span');
    content.textContent = label;
    return L.divIcon({className: 'business-marker', html: content, iconSize: [44, 44], iconAnchor: [22, 44], popupAnchor: [0, -40], tooltipAnchor: [0, -40]});
}

function businessPopup(business) {
    const content = document.createElement('div');
    const title = document.createElement('strong');
    title.textContent = business.name;
    const address = document.createElement('p');
    address.textContent = business.address;
    const meta = document.createElement('p');
    meta.textContent = business.rating === null ? 'هنوز امتیازی ندارد' : `★ ${persianNumber(business.rating)} · ${persianNumber(business.reviews)} تجربه`;
    if (business.price) meta.textContent += ` · ${'$'.repeat(business.price)}`;
    const link = document.createElement('a');
    link.href = business.url;
    link.textContent = 'مشاهده جزئیات مکان ←';
    content.append(title, address, meta, link);
    return content;
}

function initializeDiscovery(root) {
    const data = JSON.parse(document.getElementById('discovery-data').textContent);
    const status = root.querySelector('[data-map-status]');
    const center = coordinates(data.city?.latitude, data.city?.longitude);
    const mapped = data.businesses.filter(business => coordinates(business.latitude, business.longitude));
    const normalStatus = mapped.length ? `${persianNumber(mapped.length)} مکان روی نقشه · نشان‌ها را انتخاب کن` : 'در این جست‌وجو هنوز مکانی با موقعیت دقیق ثبت نشده است.';
    const map = createMap(document.getElementById('discovery-map'), center, failed => {
        status.textContent = failed ? 'تصویر نقشه بارگذاری نشد. اتصال اینترنت را بررسی کن؛ فهرست مکان‌ها در دسترس است.' : normalStatus;
    });
    const markers = new Map();
    const list = root.querySelector('#discovery-results');

    function showView(view) {
        root.dataset.view = view;
        root.querySelectorAll('[data-discovery-view]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.discoveryView === view)));
        map.invalidateSize({animate: false});
    }

    function selectBusiness(id, fromList = false) {
        const marker = markers.get(id);
        if (!marker) return;
        root.querySelectorAll('[data-business-result]').forEach(row => {
            const selected = Number(row.dataset.businessResult) === id;
            row.dataset.selected = String(selected);
            row.querySelector('[data-focus-business]')?.setAttribute('aria-pressed', String(selected));
        });
        markers.forEach((item, key) => {
            item.getElement()?.classList.toggle('is-selected', key === id);
            item.setZIndexOffset(key === id ? 1000 : 0);
        });
        if (fromList) showView('map');
        map.setView(marker.getLatLng(), Math.max(map.getZoom(), 15), {animate: !reducedMotion});
        marker.openPopup();
        if (fromList && window.matchMedia('(max-width: 1023px)').matches) {
            map.getContainer().focus({preventScroll: true});
        } else if (!fromList && window.matchMedia('(min-width: 1024px)').matches) {
            const row = document.getElementById(`result-${id}`);
            list.scrollTo({top: list.scrollTop + row.getBoundingClientRect().top - list.getBoundingClientRect().top - 12, behavior: reducedMotion ? 'instant' : 'smooth'});
        }
    }

    data.businesses.forEach((business, index) => {
        const point = coordinates(business.latitude, business.longitude);
        if (!point) return;
        const tooltip = document.createElement('span');
        tooltip.textContent = business.name;
        const marker = L.marker(point, {icon: markerIcon(persianNumber(index + 1)), title: business.name, alt: business.name})
            .addTo(map).bindTooltip(tooltip, {direction: 'top'}).bindPopup(businessPopup(business), {maxWidth: 260, autoPan: false});
        marker.on('click', () => selectBusiness(business.id));
        markers.set(business.id, marker);
    });

    function fitResults() {
        const points = mapped.map(business => [business.latitude, business.longitude]);
        if (points.length) map.fitBounds(points, {padding: [55, 75], maxZoom: 15, animate: false});
        else map.setView(center ?? iranCenter, center ? 12 : 5, {animate: false});
    }

    root.querySelectorAll('[data-focus-business]').forEach(button => button.addEventListener('click', () => selectBusiness(Number(button.dataset.focusBusiness), true)));
    root.querySelectorAll('[data-discovery-view]').forEach(button => button.addEventListener('click', () => showView(button.dataset.discoveryView)));
    const fitButton = root.querySelector('[data-fit-results]');
    fitButton.hidden = mapped.length === 0;
    fitButton.addEventListener('click', fitResults);
    root.querySelector('[data-map-controls]').hidden = false;
    status.textContent = normalStatus;
    fitResults();
    root.dataset.mapReady = 'true';
}

function initializeLocationPicker(root) {
    const cities = JSON.parse(root.querySelector('[data-map-cities]').textContent);
    const cityInput = document.getElementById(root.dataset.cityInput);
    const latitude = root.querySelector('[data-latitude]');
    const longitude = root.querySelector('[data-longitude]');
    const status = root.querySelector('[data-location-status]');
    const point = () => coordinates(latitude.value, longitude.value);
    const cityCenter = () => {
        const city = cities.find(item => item.name === cityInput?.value);
        return coordinates(city?.latitude, city?.longitude);
    };
    const map = createMap(root.querySelector('[data-location-map]'), point() ?? cityCenter(), failed => {
        if (failed) status.textContent = 'تصویر نقشه بارگذاری نشد. می‌توانید مختصات دقیق را وارد کنید.';
    });
    let marker;
    let locationRequest = 0;
    const locate = root.querySelector('[data-use-location]');

    function cancelLocationRequest() {
        locationRequest++;
        locate.disabled = false;
    }

    function placeMarker(position) {
        if (!position) return;
        if (marker) marker.setLatLng(position);
        else {
            marker = L.marker(position, {icon: markerIcon(), draggable: true, title: 'موقعیت کسب‌وکار؛ برای تغییر بکشید', alt: 'موقعیت کسب‌وکار'}).addTo(map);
            marker.on('dragend', () => setLocation(marker.getLatLng()));
        }
    }

    function setLocation(latlng) {
        cancelLocationRequest();
        const position = coordinates(latlng.lat, latlng.lng);
        if (!position) {
            if (marker && point()) marker.setLatLng(point());
            status.textContent = 'موقعیت باید در محدوده ایران باشد.';
            return;
        }
        latitude.value = position[0].toFixed(7);
        longitude.value = position[1].toFixed(7);
        placeMarker(position);
        status.textContent = 'موقعیت انتخاب شد. برای انتشار، تغییرات را ذخیره کنید.';
    }

    function clearLocation() {
        cancelLocationRequest();
        latitude.value = '';
        longitude.value = '';
        if (marker) { map.removeLayer(marker); marker = null; }
        status.textContent = 'موقعیتی انتخاب نشده؛ محل کسب‌وکار را روی نقشه مشخص کنید.';
    }

    if (point()) {
        placeMarker(point());
        map.setZoom(16);
        status.textContent = 'موقعیت ثبت‌شده نمایش داده می‌شود. برای اصلاح، نشان را جابه‌جا کنید.';
    } else status.textContent = 'برای انتخاب موقعیت، روی نقشه بزنید.';

    map.on('click', event => setLocation(event.latlng));
    root.querySelector('[data-use-map-center]').addEventListener('click', () => setLocation(map.getCenter()));
    root.querySelector('[data-clear-location]').addEventListener('click', clearLocation);
    [latitude, longitude].forEach(input => input.addEventListener('input', () => {
        cancelLocationRequest();
        if (marker) { map.removeLayer(marker); marker = null; }
        const position = point();
        if (position) {
            placeMarker(position);
            map.setView(position, 16, {animate: false});
            status.textContent = 'مختصات جدید انتخاب شد؛ تغییرات را ذخیره کنید.';
        } else status.textContent = 'هر دو مختصات معتبر را وارد کنید یا موقعیت را حذف کنید.';
    }));
    let previousCity = cityInput?.value;
    cityInput?.addEventListener('change', () => {
        if (cityInput.value === previousCity || !cities.some(city => city.name === cityInput.value)) return;
        previousCity = cityInput.value;
        clearLocation();
        const center = cityCenter();
        map.setView(center ?? iranCenter, center ? 12 : 5, {animate: false});
        status.textContent = 'شهر تغییر کرد؛ موقعیت دقیق کسب‌وکار را دوباره انتخاب کنید.';
    });
    locate.addEventListener('click', () => {
        if (!navigator.geolocation) { status.textContent = 'موقعیت‌یابی در این مرورگر در دسترس نیست؛ روی نقشه انتخاب کنید.'; return; }
        const request = ++locationRequest;
        locate.disabled = true;
        status.textContent = 'در حال دریافت موقعیت…';
        navigator.geolocation.getCurrentPosition(position => {
            if (request !== locationRequest) return;
            setLocation({lat: position.coords.latitude, lng: position.coords.longitude});
            const current = point();
            if (current) map.setView(current, 17, {animate: !reducedMotion});
        }, () => {
            if (request !== locationRequest) return;
            locate.disabled = false;
            status.textContent = 'موقعیت دریافت نشد. اجازه دسترسی را بررسی کنید یا روی نقشه انتخاب کنید.';
        }, {enableHighAccuracy: true, timeout: 10000, maximumAge: 30000});
    });
}

const discovery = document.getElementById('discovery');
if (discovery) initializeDiscovery(discovery);
document.querySelectorAll('[data-location-picker]').forEach(initializeLocationPicker);
