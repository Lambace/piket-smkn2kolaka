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
    bulanan: "Bulanan",
    semester: "Semester",
};

// ===== DROPDOWN CUSTOM (menu terkunci dalam wadah) =====
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
                    <ul className="absolute left-0 right-0 z-50 mt-1 max-h-56 overflow-auto rounded-lg border border-slate-600 bg-slate-800 py-1 shadow-2xl">
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

// ===== KOMPONEN MODAL =====
function Modal({ open, onClose, title, icon, children }) {
    if (!open) return null;
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/70 backdrop-blur-sm"
                onClick={onClose}
            />
            <div className="relative w-full max-w-md rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-2xl">
                <div className="mb-4 flex items-center justify-between border-b border-slate-700 pb-3">
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
                {children}
            </div>
        </div>
    );
}

export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const currentFilters = usePage().props.currentFilters ?? {};

    // ===== STATE KHUSUS DOWNLOAD =====
    const [downloadPeriode, setDownloadPeriode] = useState("harian");
    const [downloadFilterHari, setDownloadFilterHari] = useState("Semua Hari");
    const [modalDownload, setModalDownload] = useState(false);

    // ===== STATE KHUSUS TAMPILAN =====
    const [dariTanggal, setDariTanggal] = useState(
        currentFilters?.dari_tanggal ?? new Date().toISOString().split("T")[0],
    );
    const [sampaiTanggal, setSampaiTanggal] = useState(
        currentFilters?.sampai_tanggal ??
            new Date().toISOString().split("T")[0],
    );
    const [modalFilter, setModalFilter] = useState(false);

    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);
    const today = new Date().toISOString().split("T")[0];
    const semesterOtomatis =
        new Date().getMonth() + 1 >= 7 ? "ganjil" : "genap";

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
            {
                k: props.displayKey,
                dari_tanggal: today,
                sampai_tanggal: today,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const downloadLaporan = () => {
        const params = new URLSearchParams({
            jenis: "gabungan",
            periode: downloadPeriode,
            filter_hari: downloadFilterHari,
            tanggal: today,
            semester: semesterOtomatis,
            dari_tanggal: dariTanggal,
            sampai_tanggal: sampaiTanggal,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.laporan")}?${params.toString()}`;
    };

    const downloadDaftarHadir = () => {
        const params = new URLSearchParams({
            periode: "harian",
            tanggal: today,
            filter_hari: downloadFilterHari,
            dari_tanggal: dariTanggal,
            sampai_tanggal: sampaiTanggal,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.daftar-hadir")}?${params.toString()}`;
    };

    return (
        <div className="min-h-screen w-full max-w-full overflow-x-hidden bg-slate-900 p-2 sm:p-4 md:p-6">
            <Head title="Papan Informasi Piket" />

            {/* ===== HEADER: Logo & Judul (Kiri) ===== */}
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

            {/* ===== TOMBOL KONTROL + TRAPESIUM MEMBULAT (RESPONSIF) ===== */}
            <div className="mb-6 flex justify-center sm:mb-8 sm:justify-end">
                <div className="relative w-full max-w-[340px] sm:w-auto sm:max-w-none">
                    {/* Lapisan 1: putih miring bersudut membulat (belakang) */}
                    <div className="pointer-events-none absolute -inset-y-4 -left-6 right-2 rounded-2xl bg-white shadow-xl [transform:skewX(-20deg)] sm:-inset-y-5 sm:-left-12 sm:right-5" />

                    {/* Lapisan 2: abu-abu miring bersudut membulat (tengah) */}
                    <div className="pointer-events-none absolute -inset-y-2 -left-3 -right-3 rounded-xl bg-slate-400 shadow-lg [transform:skewX(-16deg)] sm:-left-7 sm:-right-7" />

                    {/* Lapisan 3: tombol (lurus, depan, lebar ringkas) */}
                    <div className="relative flex flex-col gap-2">
                        <button
                            onClick={() => setModalDownload(true)}
                            className="flex w-full items-center justify-center gap-1.5 rounded-xl bg-red-600 px-3 py-2 text-[11px] font-semibold text-white shadow-lg transition hover:bg-red-700 sm:w-auto sm:justify-start sm:gap-2 sm:px-4 sm:text-sm"
                        >
                            <span>📄</span>
                            <span className="whitespace-nowrap">
                                Unduh Laporan PDF
                            </span>
                            <span className="whitespace-nowrap rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-bold sm:text-[10px]">
                                {labelPeriode[downloadPeriode]} •{" "}
                                {downloadFilterHari}
                            </span>
                        </button>

                        <button
                            onClick={() => setModalFilter(true)}
                            className="flex w-full items-center justify-center gap-1.5 rounded-xl bg-indigo-600 px-3 py-2 text-[11px] font-semibold text-white shadow-lg transition hover:bg-indigo-700 sm:w-auto sm:justify-start sm:gap-2 sm:px-4 sm:text-sm"
                        >
                            <span>🖥️</span>
                            <span className="whitespace-nowrap">
                                Filter Tampilan Data
                            </span>
                            <span className="whitespace-nowrap rounded-full bg-white/20 px-2 py-0.5 text-[9px] font-bold sm:text-[10px]">
                                {currentFilters?.dari_tanggal ?? today} s/d{" "}
                                {currentFilters?.sampai_tanggal ?? today}
                            </span>
                        </button>
                    </div>
                </div>
            </div>
            {/* ===== KONTEN TAMPILAN ===== */}
            <div className="space-y-4 sm:space-y-6">
                {/* Card Petugas Piket (langsung di bawah tombol) */}
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
                title="Unduh Laporan PDF"
                icon="📄"
            >
                <div className="space-y-3">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
                            Periode Laporan
                        </label>
                        <SelectCustom
                            value={downloadPeriode}
                            onChange={setDownloadPeriode}
                            focusRing="focus:ring-red-500"
                            options={[
                                { value: "harian", label: "📅 Harian" },
                                { value: "bulanan", label: "📆 Bulanan" },
                                { value: "semester", label: "🎓 Semester" },
                            ]}
                        />
                    </div>

                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-400">
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

                    <button
                        onClick={() => {
                            downloadLaporan();
                            setModalDownload(false);
                        }}
                        className="w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-bold text-white shadow transition hover:bg-red-700"
                    >
                        📄 Download Laporan {labelPeriode[downloadPeriode]}
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
