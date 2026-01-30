<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Navigation, MapPin } from 'lucide-vue-next';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

interface Price {
    fuel_type: string;
    price: number;
    currency: string;
    recorded_at: string;
}

interface Location {
    id: number;
    name: string;
    address: string;
    latitude: number;
    longitude: number;
    source: string;
    distance: number | null;
    prices: Price[];
}

interface Props {
    locations: Location[];
    userLocation: {
        lat: number | null;
        lon: number | null;
    };
}

const props = defineProps<Props>();

const mapContainer = ref<HTMLElement | null>(null);
const isLoadingLocation = ref(false);
const locationError = ref<string | null>(null);
const selectedSource = ref<string>('all');
let map: L.Map | null = null;
let userMarker: L.Marker | null = null;
let markerClusterGroup: L.MarkerClusterGroup | null = null;

const availableSources = computed(() => {
    const sources = new Set(props.locations.map(l => l.source));
    return Array.from(sources).sort();
});

const filteredLocations = computed(() => {
    if (selectedSource.value === 'all') {
        return props.locations;
    }
    return props.locations.filter(location => location.source === selectedSource.value);
});

const requestLocation = () => {
    if (!navigator.geolocation) {
        locationError.value = 'Geolocation is not supported by your browser. You can still view all locations on the map.';
        return;
    }

    isLoadingLocation.value = true;
    locationError.value = null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            router.get(
                '/fuel-map',
                {
                    lat: position.coords.latitude,
                    lon: position.coords.longitude,
                },
                {
                    preserveState: false,
                    onFinish: () => {
                        isLoadingLocation.value = false;
                    },
                }
            );
        },
        (error) => {
            isLoadingLocation.value = false;
            if (error.code === error.PERMISSION_DENIED) {
                locationError.value = 'Location permission denied. You can still view all locations on the map.';
            } else {
                locationError.value = `Unable to get your location. You can still view all locations on the map.`;
            }
        }
    );
};

const formatFuelType = (type: string) => {
    const typeMap: Record<string, string> = {
        unleaded: 'Unleaded',
        diesel: 'Diesel',
        super_unleaded: 'Super Unleaded',
        e10: 'E10',
        e5: 'E5',
        petrol: 'Petrol',
    };
    return typeMap[type.toLowerCase()] || type.toUpperCase();
};

const formatPrice = (price: number) => {
    return `${(price / 100).toFixed(2)}p`;
};

const formatSourceName = (source: string) => {
    const nameMap: Record<string, string> = {
        sainsburys: 'Sainsburys',
        tesco: 'Tesco',
        asda: 'Asda',
        bp: 'BP',
        esso: 'Esso',
        asconagroup: 'Ascona Group',
        jet: 'JET',
        karan: 'Karan',
        morrisons: 'Morrisons',
        moto: 'Moto',
        motorfuelgroup: 'Motor Fuel Group',
        rontec: 'Rontec',
        sgn: 'SGN',
        shell: 'Shell',
    };
    return nameMap[source.toLowerCase()] || source.charAt(0).toUpperCase() + source.slice(1);
};

const getSourceColor = (source: string) => {
    const colors: Record<string, string> = {
        sainsburys: '#f97316',
        tesco: '#3b82f6',
        asda: '#16a34a',
        bp: '#eab308',
        esso: '#dc2626',
        asconagroup: '#9333ea',
        jet: '#334155',
        karan: '#ec4899',
        morrisons: '#059669',
        moto: '#4f46e5',
        motorfuelgroup: '#0891b2',
        rontec: '#d97706',
        sgn: '#65a30d',
        shell: '#e11d48',
    };
    return colors[source.toLowerCase()] || '#6b7280';
};

const kmToMiles = (km: number) => {
    return (km * 0.621371).toFixed(1);
};

const createPopupContent = (location: Location) => {
    const distanceInfo = location.distance 
        ? `<div style="margin-bottom: 8px; color: #64748b; font-size: 12px;">
             <strong>${kmToMiles(location.distance)} miles away</strong>
           </div>` 
        : '';

    const pricesHtml = location.prices.length > 0
        ? location.prices.map(price => `
            <div style="display: flex; justify-content: space-between; padding: 8px; background: #f8fafc; border-radius: 6px; margin-bottom: 6px;">
                <span style="font-weight: 500;">${formatFuelType(price.fuel_type)}</span>
                <span style="font-weight: 700; color: #0f172a;">${formatPrice(price.price)}</span>
            </div>
          `).join('')
        : '<p style="color: #94a3b8;">No prices available</p>';

    return `
        <div style="min-width: 250px; font-family: system-ui, -apple-system, sans-serif;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                <h3 style="margin: 0; font-size: 16px; font-weight: 600;">${location.name || 'Unnamed Location'}</h3>
                <span style="background: ${getSourceColor(location.source)}; color: white; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                    ${formatSourceName(location.source)}
                </span>
            </div>
            <p style="margin: 0 0 12px 0; color: #64748b; font-size: 13px;">
                ${location.address || 'No address'}
            </p>
            ${distanceInfo}
            <div style="margin-top: 12px;">
                ${pricesHtml}
            </div>
        </div>
    `;
};

const initializeMap = () => {
    if (!mapContainer.value) return;

    // Default center (UK)
    let centerLat = 54.5;
    let centerLon = -2.0;
    let zoom = 6;

    // If user location provided, center on it
    if (props.userLocation.lat && props.userLocation.lon) {
        centerLat = props.userLocation.lat;
        centerLon = props.userLocation.lon;
        zoom = 10;
    } else if (props.locations.length > 0) {
        // Otherwise center on first location
        const firstLocation = props.locations[0];
        if (firstLocation.latitude && firstLocation.longitude) {
            centerLat = firstLocation.latitude;
            centerLon = firstLocation.longitude;
            zoom = 8;
        }
    }

    map = L.map(mapContainer.value).setView([centerLat, centerLon], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19,
    }).addTo(map);

    // Add user location marker if available
    if (props.userLocation.lat && props.userLocation.lon) {
        const userIcon = L.divIcon({
            className: 'user-location-marker',
            html: '<div style="background: #3b82f6; width: 16px; height: 16px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.3);"></div>',
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });

        userMarker = L.marker([props.userLocation.lat, props.userLocation.lon], { icon: userIcon })
            .addTo(map)
            .bindPopup('<strong>Your Location</strong>');
    }

    // Create marker cluster group
    markerClusterGroup = L.markerClusterGroup({
        maxClusterRadius: 80,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false,
        zoomToBoundsOnClick: true,
        iconCreateFunction: function(cluster) {
            const count = cluster.getChildCount();
            let size = 'small';
            let sizeClass = 40;
            
            if (count > 50) {
                size = 'large';
                sizeClass = 60;
            } else if (count > 10) {
                size = 'medium';
                sizeClass = 50;
            }
            
            return L.divIcon({
                html: `<div style="width: ${sizeClass}px; height: ${sizeClass}px; display: flex; align-items: center; justify-content: center; background: #3b82f6; color: white; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); font-weight: 700; font-size: ${count > 99 ? '14px' : '16px'}">${count}</div>`,
                className: 'marker-cluster',
                iconSize: L.point(sizeClass, sizeClass)
            });
        }
    });

    // Add markers for all locations
    filteredLocations.value.forEach(location => {
        if (!location.latitude || !location.longitude) return;

        const markerColor = getSourceColor(location.source);
        
        // Modern flat design marker with icon
        const customIcon = L.divIcon({
            className: 'fuel-location-marker',
            html: `<div style="position: relative;">
                     <div style="
                       width: 36px; 
                       height: 36px; 
                       background: ${markerColor}; 
                       border-radius: 50% 50% 50% 0; 
                       transform: rotate(-45deg);
                       box-shadow: 0 3px 8px rgba(0,0,0,0.25);
                       display: flex;
                       align-items: center;
                       justify-content: center;
                       border: 3px solid white;
                     ">
                       <svg width="18" height="18" viewBox="0 0 24 24" fill="white" style="transform: rotate(45deg);">
                         <path d="M19.77 7.23l.01-.01-3.72-3.72L15 4.56l2.11 2.11c-.94.36-1.61 1.26-1.61 2.33 0 1.38 1.12 2.5 2.5 2.5.36 0 .69-.08 1-.21v7.21c0 .55-.45 1-1 1s-1-.45-1-1V14c0-1.1-.9-2-2-2h-1V5c0-1.1-.9-2-2-2H6c-1.1 0-2 .9-2 2v16h10v-7.5h1.5v5c0 1.38 1.12 2.5 2.5 2.5s2.5-1.12 2.5-2.5V9c0-.69-.28-1.32-.73-1.77zM12 10H6V5h6v5zm6 0c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                       </svg>
                     </div>
                   </div>`,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -36],
        });

        const marker = L.marker([location.latitude, location.longitude], { icon: customIcon })
            .bindPopup(createPopupContent(location), {
                maxWidth: 350,
                className: 'fuel-popup',
            });

        markerClusterGroup!.addLayer(marker);
    });

    map.addLayer(markerClusterGroup);

    // Fit bounds to show all markers
    if (filteredLocations.value.length > 0) {
        const bounds = L.latLngBounds(
            filteredLocations.value
                .filter(l => l.latitude && l.longitude)
                .map(l => [l.latitude, l.longitude] as [number, number])
        );
        
        if (props.userLocation.lat && props.userLocation.lon) {
            bounds.extend([props.userLocation.lat, props.userLocation.lon]);
        }
        
        map.fitBounds(bounds, { padding: [50, 50] });
    }
};

onMounted(() => {
    initializeMap();
});

watch(() => props.locations, () => {
    if (map) {
        // Clear existing cluster group
        if (markerClusterGroup) {
            markerClusterGroup.clearLayers();
            map.removeLayer(markerClusterGroup);
        }
        
        // Re-initialize map with new data
        map.remove();
        initializeMap();
    }
});

watch(selectedSource, () => {
    if (map) {
        // Clear existing cluster group
        if (markerClusterGroup) {
            markerClusterGroup.clearLayers();
            map.removeLayer(markerClusterGroup);
        }
        
        // Re-initialize map with new data
        map.remove();
        initializeMap();
    }
});
</script>

<template>
    <Head title="Fuel Locations Map" />

    <div class="h-screen flex flex-col bg-slate-50 dark:bg-slate-950">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 py-4 shadow-sm">
            <div class="container mx-auto max-w-7xl flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <MapPin class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        Fuel Locations Map
                    </h1>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        {{ filteredLocations.length }} locations | Click markers for prices
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <Button
                        @click="requestLocation"
                        :disabled="isLoadingLocation"
                        variant="default"
                        size="default"
                    >
                        <Navigation class="mr-2 h-4 w-4" :class="{ 'animate-spin': isLoadingLocation }" />
                        {{ userLocation.lat ? 'Update Location' : 'Use My Location' }}
                    </Button>
                </div>
            </div>
            
            <div v-if="locationError" class="container mx-auto max-w-7xl mt-3">
                <p class="text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 p-3 rounded-md">
                    {{ locationError }}
                </p>
            </div>

            <!-- Filters -->
            <div class="container mx-auto max-w-7xl mt-3">
                <div class="flex flex-wrap gap-2">
                    <Badge
                        @click="selectedSource = 'all'"
                        :variant="selectedSource === 'all' ? 'default' : 'outline'"
                        class="cursor-pointer hover:opacity-80 transition-opacity"
                    >
                        All Sources ({{ locations.length }})
                    </Badge>
                    <Badge
                        v-for="source in availableSources"
                        :key="source"
                        @click="selectedSource = source"
                        :variant="selectedSource === source ? 'default' : 'outline'"
                        :style="selectedSource === source ? `background-color: ${getSourceColor(source)}; border-color: ${getSourceColor(source)}` : ''"
                        class="cursor-pointer hover:opacity-80 transition-opacity"
                    >
                        {{ formatSourceName(source) }} ({{ locations.filter(l => l.source === source).length }})
                    </Badge>
                </div>
            </div>
        </div>

        <!-- Map Container -->
        <div ref="mapContainer" class="flex-1 relative"></div>
    </div>
</template>

<style>
/* Leaflet popup customization */
.fuel-popup .leaflet-popup-content-wrapper {
    border-radius: 8px;
    padding: 4px;
}

.fuel-popup .leaflet-popup-content {
    margin: 12px;
}

.leaflet-container {
    font-family: system-ui, -apple-system, sans-serif;
}
</style>
