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

export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const currentFilters = usePage().props.currentFilters ?? {};

    const [downloadPeriode, setDownloadPeriode] = useState("harian");
    const [downloadFilterHari, setDownloadFilterHari] = useState("Semua Hari");

    const [dariTanggal, setDariTanggal] = useState(
        currentFilters?.dari_tanggal ?? new Date().toISOString().split("T")[0],
    );
    const [sampaiTanggal, setSampaiTanggal] = useState(
        currentFilters?.sampai_tanggal ??
            new Date().toISOString().split("T")[0],
    );
    const [showFilter, setShowFilter] = useState(false);

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
        <div className="min-h-screen bg-slate-900 p-2 sm:p-4 md:p-6">
            <Head title="Papan Informasi Piket" />

            {/* Header - Fully Responsive */}
            <div className="mb-4 sm:mb-6 flex flex-col gap-3">
                {/* Logo & Title */}
                <div className="flex items-center gap-2 sm:gap-3">
                    {logoSrc ? (
                        <img
                            src={logoSrc}
                            alt="Logo"
                            className="h-10 w-10 sm:h-12 sm:w-12 rounded-xl bg-white object-contain p-1 flex-shrink-0"
                        />
                    ) : (
                        <span className="text-3xl sm:text-4xl flex-shrink-0">
                            🏫
                        </span>
                    )}
                    <div className="min-w-0 flex-1">
                        <h1 className="text-base sm:text-2xl font-extrabold text-white truncate">
                            {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"}
                        </h1>
                        <p className="text-[10px] sm:text-sm text-slate-400 truncate">
                            Papan Informasi Piket — {props.hariIni}
                        </p>
                    </div>
                </div>

                {/* Controls - Stacked on mobile */}
                <div className="flex flex-col gap-2 w-full">
                    <div className="grid grid-cols-2 gap-2">
                        <select
                            value={downloadPeriode}
                            onChange={(e) => setDownloadPeriode(e.target.value)}
                            className="w-full rounded-lg border-0 bg-slate-800 px-2 py-2 text-xs sm:text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-red-500"
                        >
                            <option value="harian">📅 Harian</option>
                            <option value="bulanan">📆 Bulanan</option>
                            <option value="semester">🎓 Semester</option>
                        </select>

                        <select
                            value={downloadFilterHari}
                            onChange={(e) =>
                                setDownloadFilterHari(e.target.value)
                            }
                            className="w-full rounded-lg border-0 bg-slate-800 px-2 py-2 text-xs sm:text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="Semua Hari"> Semua Hari</option>
                            <option value="Senin">📌 Senin</option>
                            <option value="Selasa">📌 Selasa</option>
                            <option value="Rabu">📌 Rabu</option>
                            <option value="Kamis">📌 Kamis</option>
                            <option value="Jumat">📌 Jumat</option>
                            <option value="Sabtu">📌 Sabtu</option>
                        </select>
                    </div>

                    <button
                        onClick={downloadLaporan}
                        className="w-full flex items-center justify-center gap-2 rounded-lg bg-red-600 px-3 py-2.5 text-xs sm:text-sm font-semibold text-white shadow-lg transition hover:bg-red-700"
                        title="Download PDF berdasarkan pilihan Header + Panel Ungu"
                    >
                        📄 Download Laporan {labelPeriode[downloadPeriode]}
                    </button>
                </div>
            </div>

            {/* Panel Ungu - Fully Responsive */}
            <div className="mb-4 sm:mb-6 rounded-lg bg-indigo-900/40 border border-indigo-700/50 p-3 sm:p-4 shadow-lg">
                <div className="flex flex-col gap-3">
                    <div className="flex items-center justify-between gap-2">
                        <button
                            onClick={() => setShowFilter(!showFilter)}
                            className="flex items-center gap-2 rounded-lg bg-indigo-600 px-3 sm:px-4 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-indigo-700 whitespace-nowrap flex-shrink-0"
                        >
                            <span>📅</span>
                            <span className="hidden sm:inline">
                                {showFilter
                                    ? "Sembunyikan Filter"
                                    : "Tampilkan Filter Tanggal"}
                            </span>
                            <span className="sm:hidden">Filter</span>
                        </button>

                        {(currentFilters?.dari_tanggal ||
                            currentFilters?.sampai_tanggal) && (
                            <span className="text-[10px] sm:text-xs text-slate-300 truncate">
                                Aktif:{" "}
                                <strong className="text-white">
                                    {currentFilters.dari_tanggal}
                                </strong>{" "}
                                s/d{" "}
                                <strong className="text-white">
                                    {currentFilters.sampai_tanggal}
                                </strong>
                            </span>
                        )}
                    </div>

                    {showFilter && (
                        <div className="flex flex-col gap-2">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <label className="block text-[10px] sm:text-xs font-medium text-slate-400 mb-1">
                                        Dari Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={dariTanggal}
                                        onChange={(e) =>
                                            setDariTanggal(e.target.value)
                                        }
                                        className="w-full rounded-lg border-0 bg-slate-700 px-2 sm:px-3 py-2 text-xs sm:text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                                <div>
                                    <label className="block text-[10px] sm:text-xs font-medium text-slate-400 mb-1">
                                        Sampai Tanggal
                                    </label>
                                    <input
                                        type="date"
                                        value={sampaiTanggal}
                                        onChange={(e) =>
                                            setSampaiTanggal(e.target.value)
                                        }
                                        className="w-full rounded-lg border-0 bg-slate-700 px-2 sm:px-3 py-2 text-xs sm:text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                    />
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <button
                                    onClick={applyFilter}
                                    className="rounded-lg bg-green-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-green-700 whitespace-nowrap"
                                >
                                    ✅ Terapkan
                                </button>
                                <button
                                    onClick={resetFilter}
                                    className="rounded-lg bg-slate-600 px-3 py-2 text-xs sm:text-sm font-semibold text-white transition hover:bg-slate-700 whitespace-nowrap"
                                >
                                    🔄 Reset
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Content - Responsive */}
            <div className="space-y-4 sm:space-y-6">
                <KartuAbsensiPetugas
                    data={props.absensiPetugas ?? []}
                    displayKey={props.displayKey}
                />
                <KartuStatistik stats={props.stats} />

                <DataHariIni
                    keterlambatanList={props.keterlambatanList ?? []}
                    izinKeluarList={props.izinKeluarList ?? []}
                    pelanggaranList={props.pelanggaranList ?? []}
                    bukuTamuList={props.bukuTamuList ?? []}
                />

                <div className="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartData}
                            judul="📊 Keterlambatan per Kelas"
                        />
                    </div>
                    <DonutChart
                        data={props.donutJurusan}
                        judul="🎓 Keterlambatan per Jurusan"
                    />
                </div>

                <div className="grid grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-3">
                    <TabelTerlambatTertinggi data={props.topTerlambat} />
                    <TabelPoinTertinggi data={props.topPelanggaran} />
                    <AktivitasTerbaru data={props.aktivitas} />
                </div>
            </div>

            <p className="mt-4 sm:mt-6 text-center text-[10px] sm:text-xs text-slate-600 px-2">
                © {new Date().getFullYear()}{" "}
                {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"} — Sistem Informasi
                Piket
            </p>
        </div>
    );
}
