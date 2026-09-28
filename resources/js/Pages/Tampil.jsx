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

    // ===== STATE KHUSUS DOWNLOAD (tidak mempengaruhi tampilan) =====
    const [downloadPeriode, setDownloadPeriode] = useState("harian");
    const [downloadFilterHari, setDownloadFilterHari] = useState("Semua Hari");

    // ===== STATE KHUSUS TAMPILAN (tidak mempengaruhi download) =====
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
        <div className="min-h-screen w-full max-w-full overflow-x-hidden bg-slate-900 p-2 sm:p-4 md:p-6">
            <Head title="Papan Informasi Piket" />

            {/* ===== HEADER: Logo & Judul Saja ===== */}
            <div className="mb-3 sm:mb-4 flex items-center gap-2 sm:gap-3">
                {logoSrc ? (
                    <img
                        src={logoSrc}
                        alt="Logo"
                        className="h-10 w-10 sm:h-12 sm:w-12 flex-shrink-0 rounded-xl bg-white object-contain p-1"
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

            {/* ===== SATU CARD KONTROL GABUNGAN ===== */}
            <div className="mb-4 rounded-xl border border-slate-700 bg-slate-800 p-3 shadow-lg sm:mb-6 sm:p-4">
                {/* --- BAGIAN A: KONTROL DOWNLOAD LAPORAN --- */}
                <div>
                    <p className="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 sm:text-xs">
                        📄 Unduh Laporan PDF
                    </p>
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <select
                            value={downloadPeriode}
                            onChange={(e) => setDownloadPeriode(e.target.value)}
                            className="w-full rounded-lg border-0 bg-slate-700 px-2 py-2 text-xs font-semibold text-white shadow focus:ring-2 focus:ring-red-500 sm:flex-1 sm:px-3 sm:text-sm"
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
                            className="w-full rounded-lg border-0 bg-slate-700 px-2 py-2 text-xs font-semibold text-white shadow focus:ring-2 focus:ring-blue-500 sm:flex-1 sm:px-3 sm:text-sm"
                        >
                            <option value="Semua Hari">👥 Semua Hari</option>
                            <option value="Senin">📌 Senin</option>
                            <option value="Selasa">📌 Selasa</option>
                            <option value="Rabu">📌 Rabu</option>
                            <option value="Kamis">📌 Kamis</option>
                            <option value="Jumat">📌 Jumat</option>
                            <option value="Sabtu">📌 Sabtu</option>
                        </select>

                        <button
                            onClick={downloadLaporan}
                            className="flex w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white shadow transition hover:bg-red-700 sm:w-auto sm:px-4 sm:text-sm"
                            title="Download PDF sesuai pilihan periode & hari"
                        >
                            📄 Download Laporan {labelPeriode[downloadPeriode]}
                        </button>
                    </div>
                </div>

                {/* --- GARIS PEMISAH --- */}
                <div className="my-3 border-t border-slate-700" />

                {/* --- BAGIAN B: FILTER TAMPILAN DATA --- */}
                <div>
                    <p className="mb-2 text-[10px] font-bold uppercase tracking-wider text-slate-400 sm:text-xs">
                        🖥️ Filter Tampilan Data
                    </p>
                    <div className="flex flex-wrap items-center gap-2">
                        <button
                            onClick={() => setShowFilter(!showFilter)}
                            className="flex items-center gap-2 whitespace-nowrap rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700 sm:px-4 sm:text-sm"
                        >
                            <span>📅</span>
                            <span>
                                {showFilter
                                    ? "Sembunyikan Filter"
                                    : "Tampilkan Filter Tanggal"}
                            </span>
                        </button>

                        {(currentFilters?.dari_tanggal ||
                            currentFilters?.sampai_tanggal) && (
                            <span className="truncate text-[10px] text-slate-300 sm:text-xs">
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
                        <div className="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label className="mb-1 block text-[10px] font-medium text-slate-400 sm:text-xs">
                                    Dari Tanggal
                                </label>
                                <input
                                    type="date"
                                    value={dariTanggal}
                                    onChange={(e) =>
                                        setDariTanggal(e.target.value)
                                    }
                                    className="w-full rounded-lg border-0 bg-slate-700 px-2 py-2 text-xs text-white focus:ring-2 focus:ring-indigo-500 sm:px-3 sm:text-sm"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-[10px] font-medium text-slate-400 sm:text-xs">
                                    Sampai Tanggal
                                </label>
                                <input
                                    type="date"
                                    value={sampaiTanggal}
                                    onChange={(e) =>
                                        setSampaiTanggal(e.target.value)
                                    }
                                    className="w-full rounded-lg border-0 bg-slate-700 px-2 py-2 text-xs text-white focus:ring-2 focus:ring-indigo-500 sm:px-3 sm:text-sm"
                                />
                            </div>
                            <button
                                onClick={applyFilter}
                                className="flex w-full items-center justify-center whitespace-nowrap rounded-lg bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-700 sm:text-sm lg:mt-[22px]"
                            >
                                ✅ Terapkan
                            </button>
                            <button
                                onClick={resetFilter}
                                className="flex w-full items-center justify-center whitespace-nowrap rounded-lg bg-slate-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-700 sm:text-sm lg:mt-[22px]"
                            >
                                🔄 Reset
                            </button>
                        </div>
                    )}
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

            <p className="mt-4 px-2 text-center text-[10px] text-slate-600 sm:mt-6 sm:text-xs">
                © {new Date().getFullYear()}{" "}
                {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"} — Sistem Informasi
                Piket
            </p>
        </div>
    );
}
