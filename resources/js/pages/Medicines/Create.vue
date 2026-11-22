<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Html5QrcodeScanner } from 'html5-qrcode';
import { onUnmounted, ref } from 'vue';

defineOptions({
    layout: AppLayout,
});

interface Category {
    id: number;
    name: string;
}

defineProps<{
    categories: Category[];
}>();

const form = useForm({
    code: '',
    name: '',
    category_id: '',
    price: '',
    minimum_stock: 10,
    rack_location: '',
    batch_number: '',
    quantity: 1,
    expired_date: '',
});

let html5QrcodeScanner: Html5QrcodeScanner | null = null;
const isScanning = ref(false);

const inputClass =
    'w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-800 placeholder:text-slate-400 outline-none transition-all duration-200 hover:border-slate-300 focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10';

const labelClass =
    'mb-2 block text-sm font-semibold text-slate-700';

const searchByCode = async (scannedCode: string) => {
    if (!scannedCode) return;

    form.code = scannedCode;

    try {
        const res = await fetch('/medicines/find-by-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    (document.querySelector(
                        'meta[name="csrf-token"]'
                    ) as HTMLMetaElement)?.content || '',
            },
            body: JSON.stringify({ code: scannedCode }),
        });

        const response = await res.json();

        if (response.found) {
            form.name = response.data.name;
            form.category_id = response.data.category_id;
            form.price = response.data.price;
            form.rack_location = response.data.rack_location;
            form.minimum_stock = response.data.minimum_stock;

            alert(
                'Obat ditemukan! Silakan masukkan nomor batch, jumlah, dan tanggal kadaluarsa.'
            );
        } else {
            alert(
                'Kode obat baru / belum terdaftar. Silakan lengkapi data manual.'
            );
        }
    } catch (error) {
        console.error('Gagal memeriksa kode obat', error);
    }
};

const startScanner = () => {
    if (isScanning.value) return;

    isScanning.value = true;

    setTimeout(() => {
        html5QrcodeScanner = new Html5QrcodeScanner(
            'reader',
            {
                fps: 10,
                // Kotak pemindai diperbesar & responsif mengikuti ukuran layar
                qrbox: (viewfinderWidth: number, viewfinderHeight: number) => {
                    const width = Math.min(Math.floor(viewfinderWidth * 0.8), 480);
                    const height = Math.min(Math.floor(viewfinderHeight * 0.65), 260);
                    return {
                        width: Math.max(width, 250),
                        height: Math.max(height, 150),
                    };
                },
                aspectRatio: 1.777778, // Rasio lebar 16:9 agar viewfinder besar & lega
            },
            false
        );

        html5QrcodeScanner.render(
            (decodedText) => {
                searchByCode(decodedText);
                stopScanner();
            },
            () => {
                // Mengabaikan error pembacaan per frame.
            }
        );
    }, 100);
};

const stopScanner = () => {
    if (html5QrcodeScanner) {
        const scanner = html5QrcodeScanner;
        html5QrcodeScanner = null;

        scanner.clear().catch((error) => {
            console.error('Gagal menghentikan scanner.', error);
        });
    }

    isScanning.value = false;
};

onUnmounted(() => {
    stopScanner();
});

const submit = () => {
    form.post('/medicines', {
        onSuccess: () => {
            form.reset();
            alert('Stok obat berhasil ditambahkan!');
        },
    });
};
</script>

<template>
    <Head title="Input Obat Masuk" />

    <div class="min-h-full bg-slate-50">
        <div class="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

            <!-- PAGE HEADER -->
            <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <span class="text-xs font-bold uppercase tracking-widest text-emerald-600">
                            Manajemen Inventori
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold tracking-tight text-slate-800 sm:text-3xl">
                        Input Obat Masuk
                    </h1>

                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-500">
                        Kelola stok obat dengan mudah melalui pemindaian barcode
                        atau pengisian formulir secara manual.
                    </p>
                </div>

                <div class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                        <span class="text-xl font-bold">+</span>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-emerald-600">
                            Sistem Inventori
                        </p>
                        <p class="text-sm font-bold text-emerald-800">
                            MediStock
                        </p>
                    </div>
                </div>
            </div>

            <!-- SCANNER CARD -->
            <section class="mb-7 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="flex flex-col justify-between gap-5 p-6 sm:flex-row sm:items-center sm:p-7">

                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="25"
                                height="25"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                                <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                                <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                                <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                                <path d="M7 12h10"/>
                                <path d="M7 8h2"/>
                                <path d="M7 16h2"/>
                                <path d="M15 8h2v8h-2z"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-base font-bold text-slate-800">
                                Scanner Kamera Barcode
                            </h2>

                            <p class="mt-1 max-w-md text-sm leading-relaxed text-slate-500">
                                Gunakan kamera HP atau laptop untuk membaca
                                barcode dan menemukan data obat secara otomatis.
                            </p>

                            <div class="mt-3 flex items-center gap-2">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-medium text-emerald-700">
                                    {{ isScanning ? 'Scanner sedang aktif' : 'Scanner siap digunakan' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="isScanning ? stopScanner() : startScanner()"
                        :class="isScanning
                            ? 'bg-red-500 hover:bg-red-600 shadow-red-500/20'
                            : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20'"
                        class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5"
                    >
                        <svg
                            v-if="!isScanning"
                            xmlns="http://www.w3.org/2000/svg"
                            width="18"
                            height="18"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                            <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                            <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                            <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                            <path d="M7 12h10"/>
                        </svg>

                        <span v-else>×</span>

                        {{ isScanning ? 'Tutup Kamera' : 'Buka Kamera Scanner' }}
                    </button>
                </div>

                <!-- Area Kamera Diperbesar -->
                <div
                    v-show="isScanning"
                    class="border-t border-slate-100 bg-slate-50 p-4 sm:p-6"
                >
                    <div class="mx-auto w-full max-w-3xl overflow-hidden rounded-2xl border-2 border-emerald-200 bg-white p-3 shadow-md sm:p-4">
                        <div id="reader" class="w-full"></div>
                    </div>
                </div>
            </section>

            <!-- FORM CARD -->
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <!-- Form Header -->
                <div class="border-b border-slate-100 px-6 py-6 sm:px-8">
                    <div class="flex items-center gap-3">
                        <div class="flex size-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                width="23"
                                height="23"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M12 5v14"/>
                                <path d="M5 12h14"/>
                                <rect x="3" y="3" width="18" height="18" rx="3"/>
                            </svg>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-slate-800">
                                Formulir Detail Barang Masuk
                            </h2>

                            <p class="mt-1 text-sm text-slate-500">
                                Masukkan informasi obat dan jumlah stok yang diterima.
                            </p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-6 sm:p-8">

                    <!-- INFORMASI OBAT -->
                    <div class="mb-7">
                        <div class="mb-5 flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded-md bg-emerald-100 text-xs font-bold text-emerald-700">
                                01
                            </span>

                            <h3 class="text-sm font-bold text-slate-800">
                                Informasi Obat
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">

                            <div>
                                <label :class="labelClass">
                                    Kode Barcode / QR Code
                                </label>

                                <input
                                    v-model="form.code"
                                    @blur="searchByCode(form.code)"
                                    type="text"
                                    placeholder="Scan atau ketik kode..."
                                    :class="inputClass"
                                    required
                                />

                                <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">
                                    {{ form.errors.code }}
                                </p>
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Nama Obat
                                </label>

                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Contoh: Paracetamol 500mg"
                                    :class="inputClass"
                                    required
                                />

                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Kategori Obat
                                </label>

                                <select
                                    v-model="form.category_id"
                                    :class="inputClass"
                                    required
                                >
                                    <option value="" disabled>
                                        Pilih kategori obat
                                    </option>

                                    <option
                                        v-for="cat in categories"
                                        :key="cat.id"
                                        :value="cat.id"
                                    >
                                        {{ cat.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Harga Jual (Rp)
                                </label>

                                <input
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    placeholder="Contoh: 15000"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Minimal Stok (Alert)
                                </label>

                                <input
                                    v-model="form.minimum_stock"
                                    type="number"
                                    min="0"
                                    placeholder="Minimal stok"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Lokasi Rak
                                </label>

                                <input
                                    v-model="form.rack_location"
                                    type="text"
                                    placeholder="Contoh: Rak A-1"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                        </div>
                    </div>

                    <!-- INFORMASI STOK -->
                    <div class="border-t border-slate-100 pt-7">
                        <div class="mb-5 flex items-center gap-2">
                            <span class="flex size-6 items-center justify-center rounded-md bg-emerald-100 text-xs font-bold text-emerald-700">
                                02
                            </span>

                            <h3 class="text-sm font-bold text-slate-800">
                                Informasi Stok Masuk
                            </h3>
                        </div>

                        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">

                            <div>
                                <label :class="labelClass">
                                    Nomor Batch
                                </label>

                                <input
                                    v-model="form.batch_number"
                                    type="text"
                                    placeholder="Masukkan nomor batch"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Jumlah Masuk (Qty)
                                </label>

                                <input
                                    v-model="form.quantity"
                                    type="number"
                                    min="1"
                                    placeholder="Masukkan jumlah"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    Tanggal Kadaluarsa
                                </label>

                                <input
                                    v-model="form.expired_date"
                                    type="date"
                                    :class="inputClass"
                                    required
                                />
                            </div>

                        </div>
                    </div>

                    <!-- FOOTER BUTTON -->
                    <div class="mt-8 flex flex-col-reverse items-center justify-between gap-4 border-t border-slate-100 pt-6 sm:flex-row">

                        <p class="text-xs text-slate-400">
                            Pastikan seluruh data obat sudah benar sebelum disimpan.
                        </p>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-600 px-7 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/20 transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                        >
                            <svg
                                v-if="!form.processing"
                                xmlns="http://www.w3.org/2000/svg"
                                width="19"
                                height="19"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l7 7v9a2 2 0 0 1-2 2Z"/>
                                <path d="M17 21v-8H7v8"/>
                                <path d="M7 3v5h8"/>
                            </svg>

                            <span>
                                {{ form.processing ? 'Menyimpan Data...' : 'Simpan Stok Obat' }}
                            </span>
                        </button>
                    </div>

                </form>
            </section>

            <!-- FOOTER NOTE -->
            <div class="mt-5 text-center">
                <p class="text-xs text-slate-400">
                    MediStock Pharmacy Management System
                </p>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* Memastikan elemen video dan tombol bawaan html5-qrcode tampil lebar & rapi */
:deep(#reader) {
    border: none !important;
}

:deep(#reader video) {
    width: 100% !important;
    min-height: 320px;
    border-radius: 0.75rem;
    object-fit: cover;
}

:deep(#reader__dashboard_section_csr button),
:deep(#reader__dashboard_section_fsr button) {
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background-color: #059669;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
    margin-top: 0.5rem;
    cursor: pointer;
}
</style>