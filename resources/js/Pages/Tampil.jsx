import { Head, usePoll, usePage, router } from "@inertiajs/react";
import { useState } from "react";
import KartuAbsensiPetugas from "./Dashboard/Components/KartuAbsensiPetugas";
import KartuStatistik from "./Dashboard/Components/KartuStatistik";
import DataHariIni from "./Dashboard/Components/DataHariIni";
import GrafikKeterlambatan from "./Dashboard/Components/GrafikKeterlambatan";
import DonutChart from "./Dashboard/Components/DonutChart";
import TabelTerlambatTertinggi from "./Dashboard/Components/TabelTerlambatTertinggi";
import TabelPoinTertinggi from "./Dashboard/Components/TabelPoinTertinggi";
import AktivitasTerbaru from "./Dashboard/Components/AktivitasTerbaru";

const labelPeriode = {
    harian: "Harian",
    bulanan: "Bulanan (Rentang)",
    semester: "Semester",
};

/// ===== DROPDOWN CUSTOM =====
function SelectCustom({
    value,
    onChange,
    options,
    focusRing = "focus:ring-red-500",
}) {
    const [open, setOpen] = useState(false);
    const current = options.find((o) => o.value === value);

    return (
        <div className="relative w-full">
            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                className={`flex w-full items-center justify-between rounded-lg border-0 bg-slate-700 px-3 py-2 text-xs font-semibold text-white shadow focus:ring-2 ${focusRing} sm:text-sm`}
            >
                <span className="truncate">{current?.label ?? value}</span>
                <span
                    className={`ml-2 text-slate-400 transition-transform ${open ? "rotate-180" : ""}`}
                >
                    ▾
                </span>
            </button>

            {open && (
                <>
                    <div
                        className="fixed inset-0 z-40"
                        onClick={() => setOpen(false)}
                    />
                    {/* 🔑 KUNCI: max-h-32 (128px) agar dropdown tidak terlalu panjang */}
                    <ul className="absolute left-0 right-0 z-50 mt-1 max-h-32 overflow-auto rounded-lg border border-slate-600 bg-slate-800 py-1 shadow-2xl">
                        {options.map((o) => (
                            <li key={o.value}>
                                <button
                                    type="button"
                                    onClick={() => {
                                        onChange(o.value);
                                        setOpen(false);
                                    }}
                                    className={`flex w-full items-center gap-2 px-3 py-2 text-left text-xs font-semibold sm:text-sm ${
                                        o.value === value
                                            ? "bg-indigo-600 text-white"
                                            : "text-slate-200 hover:bg-slate-700"
                                    }`}
                                >
                                    {o.label}
                                </button>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </div>
    );
}

        // ===== KOMPONEN MODAL (FLEX & SCROLLABLE) =====
// ===== KOMPONEN MODAL =====
function Modal({ open, onClose, title, icon, children }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                onClick={onClose}
            />
            <div className="relative flex flex-col w-full max-w-md max-h-[90vh] rounded-xl border border-slate-700 bg-slate-800 shadow-2xl">
                <div className="flex items-center justify-between border-b border-slate-700 p-5 sticky top-0 bg-slate-800 z-10 rounded-t-xl">
                    <h3 className="text-base font-bold text-white">
                        {icon} {title}
                    </h3>
                    <button
                        onClick={onClose}
                        className="rounded-lg bg-slate-700 px-2.5 py-1 text-sm text-slate-300 transition hover:bg-slate-600"
                        title="Tutup"
                    >
                        ✕
                    </button>
                </div>
                {/* 🔑 KUNCI: pb-48 = padding bottom 12rem (192px) untuk ruang dropdown */}
                <div className="flex-1 overflow-y-auto p-5 space-y-4 pb-48">
                    {children}
                </div>
            </div>
        </div>
    );
}
export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const currentFilters = usePage().props.currentFilters ?? {};
    const today = new Date().toISOString().split("T")[0];

    // ===== STATE KHUSUS DOWNLOAD =====
    const [downloadPeriode, setDownloadPeriode] = useState("harian");
    const [downloadFilterHari, setDownloadFilterHari] = useState("Semua Hari");
    const [modalDownload, setModalDownload] = useState(false);

    // ===== STATE KHUSUS RENTANG BULAN (DATE PICKER) =====
    const [downloadDari, setDownloadDari] = useState(today);
    const [downloadSampai, setDownloadSampai] = useState(today);

    // ===== STATE KHUSUS TAMPILAN =====
    const [dariTanggal, setDariTanggal] = useState(
        currentFilters?.dari_tanggal ?? today,
    );
    const [sampaiTanggal, setSampaiTanggal] = useState(
        currentFilters?.sampai_tanggal ?? today,
    );
    const [modalFilter, setModalFilter] = useState(false);

    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);
    const semesterOtomatis =
        new Date().getMonth() + 1 >= 7 ? "ganjil" : "genap";

    // ===== HANDLERS =====
    const handleDownloadPeriodeChange = (value) => {
        setDownloadPeriode(value);
        // Opsional: reset ke hari ini saat ganti ke bulanan jika kosong
        if (value === "bulanan" && (!downloadDari || !downloadSampai)) {
            setDownloadDari(today);
            setDownloadSampai(today);
        }
    };

    const applyFilter = () => {
        router.get(
            route("tampil"),
            {
                k: props.displayKey,
                dari_tanggal: dariTanggal,
                sampai_tanggal: sampaiTanggal,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const resetFilter = () => {
        setDariTanggal(today);
        setSampaiTanggal(today);
        router.get(
            route("tampil"),
            { k: props.displayKey, dari_tanggal: today, sampai_tanggal: today },
            { preserveState: true, preserveScroll: true },
        );
    };

    const downloadLaporan = () => {
        const params = new URLSearchParams({
            jenis: "gabungan",
            filter_hari: downloadFilterHari,
            semester: semesterOtomatis,
        });

        // Jika bulanan, kirim sebagai 'rentang' agar backend pakai dari & sampai
        if (downloadPeriode === "bulanan") {
            params.set("periode", "rentang");
            params.set("dari", downloadDari);
            params.set("sampai", downloadSampai);
        } else {
            params.set("periode", downloadPeriode);
            params.set("tanggal", today);
        }

        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.laporan")}?${params.toString()}`;
    };

    const downloadDaftarHadir = () => {
        const params = new URLSearchParams({
            filter_hari: downloadFilterHari,
        });

        if (downloadPeriode === "bulanan") {
            params.set("periode", "rentang");
            params.set("dari", downloadDari);
            params.set("sampai", downloadSampai);
        } else {
            params.set("periode", downloadPeriode);
            params.set("tanggal", today);
        }

        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.daftar-hadir")}?${params.toString()}`;
    };

    return (
        <div className="min-h-screen w-full max-w-full overflow-x-hidden bg-slate-900 p-2 sm:p-4 md:p-6">
            <Head title="Papan Informasi Piket" />

            {/* ===== HEADER ===== */}
            <div className="mb-3 flex items-center gap-2 sm:mb-4 sm:gap-3">
                {logoSrc ? (
                    <img
                        src={logoSrc}
                        alt="Logo"
                        className="h-10 w-10 flex-shrink-0 rounded-xl bg-white object-contain p-1 sm:h-12 sm:w-12"
                    />
                ) : (
                    <span className="flex-shrink-0 text-3xl sm:text-4xl">
                        🏫
                    </span>
                )}
                <div className="min-w-0 flex-1">
                    <h1 className="truncate text-base font-extrabold text-white sm:text-2xl">
                        {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"}
                    </h1>
                    <p className="truncate text-[10px] text-slate-400 sm:text-sm">
                        Papan Informasi Piket — {props.hariIni}
                    </p>
                </div>
            </div>

            {/* ===== TOMBOL KONTROL ===== */}
            <div className="mb-5 mt-8 flex justify-end sm:mb-8 sm:mt-12">
                <div className="relative w-1/4 sm:w-auto sm:max-w-none">
                    <div className="pointer-events-none absolute -inset-y-1.5 -left-[52px] w-9 sm:-inset-y-5 sm:-left-[126px] sm:w-[66px]">
                        <div className="absolute left-3 top-0 h-[84%] w-6 rounded-md bg-slate-400 shadow-md [transform:skewX(-14deg)] sm:left-[22px] sm:w-11 sm:rounded-xl sm:shadow-lg sm:[transform:skewX(-20deg)]" />
                        <div className="absolute left-0 top-[16%] h-[84%] w-6 rounded-md bg-gray-300 shadow-md [transform:skewX(-14deg)] sm:w-11 sm:rounded-xl sm:shadow-lg sm:[transform:skewX(-20deg)]" />
                    </div>
                    <div className="pointer-events-none absolute -inset-y-1.5 -left-2 -right-6 rounded-lg bg-white shadow-lg [transform:skewX(-14deg)] sm:-inset-y-5 sm:-left-12 sm:-right-14 sm:rounded-2xl sm:shadow-xl sm:[transform:skewX(-20deg)]" />
                    <div className="pointer-events-none absolute -inset-y-0.5 -left-1 -right-4 rounded-md bg-slate-400 shadow-md [transform:skewX(-12deg)] sm:-inset-y-1.5 sm:-left-7 sm:-right-10 sm:rounded-xl sm:shadow-lg sm:[transform:skewX(-16deg)]" />

                    <div className="relative flex flex-col gap-1 sm:gap-2">
                        <button
                            onClick={() => setModalDownload(true)}
                            className="flex w-full items-center justify-center gap-0.5 rounded-md bg-red-600 px-1.5 py-1 text-[8px] font-semibold text-white shadow-md transition hover:bg-red-700 sm:w-auto sm:justify-start sm:gap-2 sm:rounded-xl sm:px-4 sm:py-2 sm:text-sm"
                            title="Unduh Laporan PDF"
                        >
                            <span className="text-[9px] sm:text-base">📄</span>
                            <span className="truncate">Laporan</span>
                            <span className="hidden whitespace-nowrap rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold sm:inline-block">
                                {labelPeriode[downloadPeriode]} •{" "}
                                {downloadFilterHari}
                            </span>
                        </button>

                        <button
                            onClick={() => setModalFilter(true)}
                            className="flex w-full items-center justify-center gap-0.5 rounded-md bg-indigo-600 px-1.5 py-1 text-[8px] font-semibold text-white shadow-md transition hover:bg-indigo-700 sm:w-auto sm:justify-start sm:gap-2 sm:rounded-xl sm:px-4 sm:py-2 sm:text-sm"
                            title="Filter Tampilan Data"
                        >
                            <span className="text-[9px] sm:text-base">🖥️</span>
                            <span className="truncate">Filter</span>
                            <span className="hidden whitespace-nowrap rounded-full bg-white/20 px-2 py-0.5 text-[10px] font-bold sm:inline-block">
                                {currentFilters?.dari_tanggal ?? today} s/d{" "}
                                {currentFilters?.sampai_tanggal ?? today}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {/* ===== KONTEN TAMPILAN ===== */}
            <div className="space-y-4 sm:space-y-6">
                <div className="min-w-0">
                    <KartuAbsensiPetugas
                        data={props.absensiPetugas ?? []}
                        displayKey={props.displayKey}
                    />
                </div>
                <div className="min-w-0">
                    <KartuStatistik stats={props.stats} />
                </div>
                <div className="min-w-0">
                    <DataHariIni
                        keterlambatanList={props.keterlambatanList ?? []}
                        izinKeluarList={props.izinKeluarList ?? []}
                        pelanggaranList={props.pelanggaranList ?? []}
                        bukuTamuList={props.bukuTamuList ?? []}
                    />
                </div>
                <div className="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <div className="min-w-0 lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartData}
                            judul="📊 Keterlambatan per Kelas"
                        />
                    </div>
                    <div className="min-w-0">
                        <DonutChart
                            data={props.donutJurusan}
                            judul="🎓 Keterlambatan per Jurusan"
                        />
                    </div>
                </div>
                <div className="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <div className="min-w-0">
                        <TabelTerlambatTertinggi data={props.topTerlambat} />
                    </div>
                    <div className="min-w-0">
                        <TabelPoinTertinggi data={props.topPelanggaran} />
                    </div>
                    <div className="min-w-0">
                        <AktivitasTerbaru data={props.aktivitas} />
                    </div>
                </div>
            </div>

            {/* ===== MODAL 1: UNDUH LAPORAN PDF ===== */}
            <Modal
                open={modalDownload}
                onClose={() => setModalDownload(false)}
                title="Unduh Laporan"
                icon="📄"
            >
                {/* 1. Periode */}
                <div>
                    <label className="mb-1.5 block text-xs font-semibold text-slate-400">
                        Periode Laporan
                    </label>
                    <SelectCustom
                        value={downloadPeriode}
                        onChange={handleDownloadPeriodeChange}
                        focusRing="focus:ring-red-500"
                        options={[
                            { value: "harian", label: "📅 Harian" },
                            {
                                value: "bulanan",
                                label: "📆 Bulanan (Rentang Tanggal)",
                            },
                            { value: "semester", label: "🎓 Semester" },
                        ]}
                    />
                </div>

                {/* 2. Panel Rentang (Muncul hanya jika Bulanan, Layout 2 Kolom) */}
                {downloadPeriode === "bulanan" && (
                    <div className="rounded-lg border border-indigo-500/30 bg-indigo-500/10 p-3">
                        <p className="mb-2 text-xs font-semibold text-indigo-300">
                            Pilih Rentang Tanggal:
                        </p>
                        <div className="grid grid-cols-2 gap-3">
                            <div>
                                <label className="mb-1 block text-[10px] font-medium text-slate-400">
                                    Dari
                                </label>
                                <input
                                    type="date"
                                    value={downloadDari}
                                    max={downloadSampai}
                                    onChange={(e) =>
                                        setDownloadDari(e.target.value)
                                    }
                                    className="w-full rounded border-0 bg-slate-900 px-2 py-1.5 text-xs text-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-[10px] font-medium text-slate-400">
                                    Sampai
                                </label>
                                <input
                                    type="date"
                                    value={downloadSampai}
                                    min={downloadDari}
                                    onChange={(e) =>
                                        setDownloadSampai(e.target.value)
                                    }
                                    className="w-full rounded border-0 bg-slate-900 px-2 py-1.5 text-xs text-white focus:ring-1 focus:ring-indigo-500"
                                />
                            </div>
                        </div>
                    </div>
                )}

                {/* 3. Filter Hari */}
                <div>
                    <label className="mb-1.5 block text-xs font-semibold text-slate-400">
                        Filter Hari
                    </label>
                    <SelectCustom
                        value={downloadFilterHari}
                        onChange={setDownloadFilterHari}
                        focusRing="focus:ring-blue-500"
                        options={[
                            { value: "Semua Hari", label: "👥 Semua Hari" },
                            { value: "Senin", label: "📌 Senin" },
                            { value: "Selasa", label: "📌 Selasa" },
                            { value: "Rabu", label: "📌 Rabu" },
                            { value: "Kamis", label: "📌 Kamis" },
                            { value: "Jumat", label: "📌 Jumat" },
                            { value: "Sabtu", label: "📌 Sabtu" },
                        ]}
                    />
                </div>

                {/* 4. Tombol Aksi (Grid 2 Kolom agar kompak) */}
                <div className="grid grid-cols-2 gap-3 pt-2">
                    <button
                        onClick={() => {
                            downloadLaporan();
                            setModalDownload(false);
                        }}
                        className="flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700"
                    >
                        <span>📄</span> Download
                    </button>
                    
                </div>
            </Modal>

            {/* ===== MODAL 2: FILTER TAMPILAN DATA ===== */}
            <Modal
                open={modalFilter}
                onClose={() => setModalFilter(false)}
                title="Filter Tampilan Data"
                icon="🖥️"
            >
                <div className="space-y-3">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
                            Dari Tanggal
                        </label>
                        <input
                            type="date"
                            value={dariTanggal}
                            max={sampaiTanggal}
                            onChange={(e) => setDariTanggal(e.target.value)}
                            className="w-full rounded-lg border-0 bg-slate-700 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
                            Sampai Tanggal
                        </label>
                        <input
                            type="date"
                            value={sampaiTanggal}
                            min={dariTanggal}
                            onChange={(e) => setSampaiTanggal(e.target.value)}
                            className="w-full rounded-lg border-0 bg-slate-700 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                        />
                    </div>
                    <div className="grid grid-cols-2 gap-2 pt-1">
                        <button
                            onClick={() => {
                                applyFilter();
                                setModalFilter(false);
                            }}
                            className="rounded-lg bg-green-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-green-700"
                        >
                            ✅ Terapkan
                        </button>
                        <button
                            onClick={() => {
                                resetFilter();
                                setModalFilter(false);
                            }}
                            className="rounded-lg bg-slate-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-slate-700"
                        >
                            🔄 Reset
                        </button>
                    </div>
                </div>
            </Modal>

            <p className="mt-4 px-2 text-center text-[10px] text-slate-600 sm:mt-6 sm:text-xs">
                © {new Date().getFullYear()}{" "}
                {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"} — Sistem Informasi
                Piket
            </p>
        </div>
    );
}
