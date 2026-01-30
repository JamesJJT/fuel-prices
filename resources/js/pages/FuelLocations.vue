<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { MapPin, Navigation, Fuel, Clock } from 'lucide-vue-next';

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

const isLoadingLocation = ref(false);
const locationError = ref<string | null>(null);
const selectedSource = ref<string | null>(null);

const requestLocation = () => {
    if (!navigator.geolocation) {
        locationError.value = 'Geolocation is not supported by your browser. You can still browse all locations below.';
        return;
    }

    isLoadingLocation.value = true;
    locationError.value = null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            router.get(
                '/fuel-locations',
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
                locationError.value = 'Location permission denied. You can still browse all locations below.';
            } else {
                locationError.value = `Unable to get your location. You can still browse all locations below.`;
            }
        }
    );
};

const hasUserLocation = computed(() => {
    return props.userLocation.lat !== null && props.userLocation.lon !== null;
});

const availableSources = computed(() => {
    const sources = [...new Set(props.locations.map(l => l.source))];
    return sources.sort();
});

const filteredLocations = computed(() => {
    if (!selectedSource.value) {
        return props.locations;
    }
    return props.locations.filter(l => l.source === selectedSource.value);
});

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

const formatPrice = (price: number, currency: string) => {
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
        sainsburys: 'bg-orange-500',
        tesco: 'bg-blue-500',
        asda: 'bg-green-600',
        bp: 'bg-yellow-500',
        esso: 'bg-red-600',
        asconagroup: 'bg-purple-600',
        jet: 'bg-slate-700',
        karan: 'bg-pink-500',
        morrisons: 'bg-emerald-600',
        moto: 'bg-indigo-600',
        motorfuelgroup: 'bg-cyan-600',
        rontec: 'bg-amber-600',
        sgn: 'bg-lime-600',
        shell: 'bg-rose-600',
    };
    return colors[source.toLowerCase()] || 'bg-gray-500';
};

const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
};

const kmToMiles = (km: number) => {
    return (km * 0.621371).toFixed(1);
};

// Removed auto-location request to avoid popup issues
</script>

<template>
    <Head title="Fuel Locations" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-950 dark:to-slate-900">
        <div class="container mx-auto px-4 py-8 max-w-7xl">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-4xl font-bold text-slate-900 dark:text-slate-100 mb-2 flex items-center gap-3">
                    <Fuel class="h-10 w-10 text-blue-600 dark:text-blue-400" />
                    Fuel Locations
                </h1>
                <p class="text-slate-600 dark:text-slate-400">
                    Find the nearest fuel stations and compare prices
                </p>
            </div>

            <!-- Location Button and Filters -->
            <div class="mb-6 space-y-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <Button
                        @click="requestLocation"
                        :disabled="isLoadingLocation"
                        variant="default"
                        size="lg"
                        class="w-full sm:w-auto"
                    >
                        <Navigation class="mr-2 h-4 w-4" :class="{ 'animate-spin': isLoadingLocation }" />
                        {{ hasUserLocation ? 'Update Location' : 'Use My Location' }}
                    </Button>
                </div>
                
                <p v-if="locationError" class="text-sm text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/30 p-3 rounded-md">
                    {{ locationError }}
                </p>
                
                <p v-else-if="hasUserLocation" class="text-sm text-green-600 dark:text-green-400">
                    ✓ Showing locations sorted by distance from you
                </p>
                
                <p v-else class="text-sm text-slate-500 dark:text-slate-400">
                    Click "Use My Location" to sort stations by distance, or browse all locations below
                </p>

                <!-- Source Filters -->
                <div v-if="availableSources.length > 0" class="flex flex-wrap gap-2 items-center">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-300">Filter by:</span>
                    <Badge
                        @click="selectedSource = null"
                        :variant="selectedSource === null ? 'default' : 'outline'"
                        class="cursor-pointer hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                    >
                        All ({{ locations.length }})
                    </Badge>
                    <Badge
                        v-for="source in availableSources"
                        :key="source"
                        @click="selectedSource = selectedSource === source ? null : source"
                        :class="[
                            selectedSource === source ? getSourceColor(source) : '',
                            'cursor-pointer hover:opacity-80 transition-opacity'
                        ]"
                        :variant="selectedSource === source ? 'default' : 'outline'"
                    >
                        {{ formatSourceName(source) }} ({{ locations.filter(l => l.source === source).length }})
                    </Badge>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="isLoadingLocation" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card v-for="i in 6" :key="i">
                    <CardHeader>
                        <Skeleton class="h-6 w-3/4 mb-2" />
                        <Skeleton class="h-4 w-full" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton class="h-20 w-full" />
                    </CardContent>
                </Card>
            </div>

            <!-- Locations Grid -->
            <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card
                    v-for="location in filteredLocations"
                    :key="location.id"
                    class="hover:shadow-lg transition-shadow duration-200 border-2 hover:border-blue-500 dark:hover:border-blue-400"
                >
                    <CardHeader>
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1">
                                <CardTitle class="text-xl mb-1">{{ location.name || 'Unnamed Location' }}</CardTitle>
                                <CardDescription class="flex items-start gap-1.5 text-sm">
                                    <MapPin class="h-4 w-4 shrink-0 mt-0.5" />
                                    <span class="line-clamp-2">{{ location.address || 'No address' }}</span>
                                </CardDescription>
                            </div>
                            <Badge :class="getSourceColor(location.source)" class="shrink-0">
                                {{ formatSourceName(location.source) }}
                            </Badge>
                        </div>

                        <div v-if="location.distance !== null" class="mt-2">
                            <Badge variant="secondary" class="text-xs">
                                <Navigation class="h-3 w-3 mr-1" />
                                {{ kmToMiles(location.distance) }} miles away
                            </Badge>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div v-if="location.prices.length > 0" class="space-y-3">
                            <div
                                v-for="price in location.prices"
                                :key="price.fuel_type"
                                class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800 rounded-lg"
                            >
                                <div class="flex items-center gap-2">
                                    <Fuel class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                                    <span class="font-medium text-sm text-slate-900 dark:text-slate-100">
                                        {{ formatFuelType(price.fuel_type) }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-slate-900 dark:text-slate-100">
                                        {{ formatPrice(price.price, price.currency) }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1">
                                        <Clock class="h-3 w-3" />
                                        {{ formatTimeAgo(price.recorded_at) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-slate-500 dark:text-slate-400">
                            No prices available
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Empty State -->
            <div
                v-if="!isLoadingLocation && filteredLocations.length === 0"
                class="text-center py-12"
            >
                <Fuel class="h-16 w-16 text-slate-300 dark:text-slate-700 mx-auto mb-4" />
                <h3 class="text-xl font-semibold text-slate-900 dark:text-slate-100 mb-2">
                    {{ selectedSource ? 'No locations found for this filter' : 'No locations found' }}
                </h3>
                <p class="text-slate-600 dark:text-slate-400">
                    {{ selectedSource ? 'Try selecting a different filter' : 'Try updating your location or check back later' }}
                </p>
            </div>
        </div>
    </div>
</template>
