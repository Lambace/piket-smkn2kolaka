import { Head, usePoll, usePage, router } from "@inertiajs/react";
import { useEffect, useState } from "react";
import KartuAbsensiPetugas from "./Dashboard/Components/KartuAbsensiPetugas";
import KartuStatistik from "./Dashboard/Components/KartuStatistik";
import DataHariIni from "./Dashboard/Components/DataHariIni";
import GrafikKeterlambatan from "./Dashboard/Components/GrafikKeterlambatan";
import DonutChart from "./Dashboard/Components/DonutChart";
import TabelTerlambatTertinggi from "./Dashboard/Components/TabelTerlambatTertinggi";
import TabelPoinTertinggi from "./Dashboard/Components/TabelPoinTertinggi";
import AktivitasTerbaru from "./Dashboard/Components/AktivitasTerbaru";

// ===== PERBAIKAN: Opsi "mingguan" dihapus =====
const labelPeriode = {
    harian: "Harian",
    bulanan: "Bulanan",
    semester: "Semester",
};

export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const currentFilters = usePage().props.currentFilters ?? {};

    const [now, setNow] = useState(new Date());

    // ===== STATE KHUSUS UNTUK DOWNLOAD (HEADER) =====
    const [downloadPeriode, setDownloadPeriode] = useState("harian");
    const [downloadFilterHari, setDownloadFilterHari] = useState("Semua Hari");

    // ===== STATE KHUSUS UNTUK TAMPILAN (PANEL UNGU) =====
    const [dariTanggal, setDariTanggal] = useState(
        currentFilters?.dari_tanggal ?? new Date().toISOString().split("T")[0],
    );
    const [sampaiTanggal, setSampaiTanggal] = useState(
        currentFilters?.sampai_tanggal ??
            new Date().toISOString().split("T")[0],
    );
    const [showFilter, setShowFilter] = useState(false);

    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);
    const today = new Date().toISOString().split("T")[0];
    const semesterOtomatis =
        new Date().getMonth() + 1 >= 7 ? "ganjil" : "genap";

    // ===== FUNGSI PANEL UNGU: Hanya mengirim tanggal ke backend untuk tampilan =====
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

    // ===== FUNGSI DOWNLOAD: Menggabungkan state Header + Panel Ungu =====
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
        <div className="min-h-screen bg-slate-900 p-4 sm:p-6">
            <Head title="Papan Informasi Piket" />

            {/* Header */}
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    {logoSrc ? (
                        <img
                            src={logoSrc}
                            alt="Logo"
                            className="h-12 w-12 rounded-xl bg-white object-contain p-1"
                        />
                    ) : (
                        <span className="text-4xl">🏫</span>
                    )}
                    <div>
                        <h1 className="text-2xl font-extrabold text-white">
                            {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"}
                        </h1>
                        <p className="text-sm text-slate-400">
                            Papan Informasi Piket — {props.hariIni}
                        </p>
                    </div>
                </div>

                <div className="flex flex-col items-end gap-2">
                    <div className="text-right">
                        <div className="font-mono text-4xl font-bold text-white">
                            {now.toLocaleTimeString("id-ID", {
                                hour: "2-digit",
                                minute: "2-digit",
                                second: "2-digit",
                            })}
                        </div>
                        <p className="text-xs text-slate-500">
                            Memperbarui otomatis tiap 60 detik
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* DROPDOWN HEADER: Opsi Mingguan DIHAPUS */}
                        <select
                            value={downloadPeriode}
                            onChange={(e) => setDownloadPeriode(e.target.value)}
                            className="rounded-lg border-0 bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-red-500"
                        >
                            <option value="harian">📅 Harian</option>
                            <option value="bulanan">📆 Bulanan</option>
                            <option value="semester"> Semester</option>
                        </select>

                        <select
                            value={downloadFilterHari}
                            onChange={(e) =>
                                setDownloadFilterHari(e.target.value)
                            }
                            className="rounded-lg border-0 bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-blue-500"
                        >
                            <option value="Semua Hari">👥 Semua Hari</option>
                            <option value="Senin"> Senin</option>
                            <option value="Selasa">📌 Selasa</option>
                            <option value="Rabu">📌 Rabu</option>
                            <option value="Kamis">📌 Kamis</option>
                            <option value="Jumat"> Jumat</option>
                            <option value="Sabtu">📌 Sabtu</option>
                        </select>

                        <button
                            onClick={downloadLaporan}
                            className="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-red-700"
                            title="Download PDF berdasarkan pilihan Header + Panel Ungu"
                        >
                            Download Laporan {labelPeriode[downloadPeriode]}
                        </button>
                    </div>
                </div>
            </div>

            {/* ===== PANEL UNGU: Hanya memfilter tampilan ===== */}
            <div className="mb-6 rounded-lg bg-indigo-900/40 border border-indigo-700/50 p-4 shadow-lg">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setShowFilter(!showFilter)}
                            className="flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700"
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
                            <span className="text-sm text-slate-300">
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
                        <div className="flex flex-wrap items-end gap-3">
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Dari Tanggal
                                </label>
                                <input
                                    type="date"
                                    value={dariTanggal}
                                    onChange={(e) =>
                                        setDariTanggal(e.target.value)
                                    }
                                    className="rounded-lg border-0 bg-slate-700 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Sampai Tanggal
                                </label>
                                <input
                                    type="date"
                                    value={sampaiTanggal}
                                    onChange={(e) =>
                                        setSampaiTanggal(e.target.value)
                                    }
                                    className="rounded-lg border-0 bg-slate-700 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                            <button
                                onClick={applyFilter}
                                className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-700"
                            >
                                ✅ Terapkan
                            </button>
                            <button
                                onClick={resetFilter}
                                className="rounded-lg bg-slate-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-700"
                            >
                                🔄 Reset
                            </button>
                        </div>
                    )}
                </div>
            </div>

            {/* ===== KONTEN TAMPILAN ===== */}
            <div className="space-y-6">
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

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
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

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <TabelTerlambatTertinggi data={props.topTerlambat} />
                    <TabelPoinTertinggi data={props.topPelanggaran} />
                    <AktivitasTerbaru data={props.aktivitas} />
                </div>
            </div>

            <p className="mt-6 text-center text-xs text-slate-600">
                © {new Date().getFullYear()}{" "}
                {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"} — Sistem Informasi
                Piket
            </p>
        </div>
    );
}
