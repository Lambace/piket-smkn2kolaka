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

const labelPeriode = {
    harian: "Harian",
    mingguan: "Mingguan",
    bulanan: "Bulanan",
    semester: "Semester",
};

export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const currentFilters = usePage().props.currentFilters ?? {};
    const filterHariFromProps = usePage().props.filter_hari ?? "Semua Hari";

    const [now, setNow] = useState(new Date());
    const [periode, setPeriode] = useState("harian");
    const [filterHari, setFilterHari] = useState(filterHariFromProps);

    // ✅ STATE UNTUK FILTER RENTANG TANGGAL
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

    const hariOptions = [
        "Semua Hari",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
    ];

    // ✅ FUNGSI UNTUK MENERAPKAN FILTER TANGGAL + HARI
    const applyFilter = () => {
        router.get(
            route("tampil"),
            {
                k: props.displayKey,
                dari_tanggal: dariTanggal,
                sampai_tanggal: sampaiTanggal,
                filter_hari: filterHari,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // ✅ FUNGSI UNTUK RESET FILTER KE HARI INI
    const resetFilter = () => {
        setDariTanggal(today);
        setSampaiTanggal(today);
        setFilterHari("Semua Hari");
        router.get(
            route("tampil"),
            { k: props.displayKey, filter_hari: "Semua Hari" },
            { preserveState: true, preserveScroll: true },
        );
    };

    // ✅ FUNGSI UNTUK MENGUBAH PERIODE
    const handlePeriodeChange = (e) => {
        const newPeriode = e.target.value;
        setPeriode(newPeriode);
        router.get(
            route("tampil"),
            {
                k: props.displayKey,
                dari_tanggal: dariTanggal,
                sampai_tanggal: sampaiTanggal,
                filter_hari: filterHari,
                periode_tampilan: newPeriode,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    // ✅ FUNGSI UNTUK MENGUBAH FILTER HARI
    const handleFilterHariChange = (e) => {
        const newFilterHari = e.target.value;
        setFilterHari(newFilterHari);
        router.get(
            route("tampil"),
            {
                k: props.displayKey,
                dari_tanggal: dariTanggal,
                sampai_tanggal: sampaiTanggal,
                filter_hari: newFilterHari,
                periode_tampilan: periode,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const downloadLaporan = () => {
        const params = new URLSearchParams({
            jenis: "gabungan",
            periode,
            tanggal: today,
            semester: semesterOtomatis,
            filter_hari: filterHari,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.laporan")}?${params.toString()}`;
    };

    const downloadDaftarHadir = () => {
        const params = new URLSearchParams({
            periode: "harian",
            tanggal: today,
            filter_hari: filterHari,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.daftar-hadir")}?${params.toString()}`;
    };

    return (
        <div className="min-h-screen bg-slate-900 p-4 sm:p-6">
            <Head title="Papan Informasi Piket" />

            {/* Tombol Tentang Aplikasi */}
            <a
                href={route("papan.informasi")}
                target="_blank"
                rel="noopener noreferrer"
                className="fixed bottom-4 right-4 z-50 flex items-center gap-2 rounded-lg border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold text-white shadow-lg backdrop-blur-md transition hover:bg-white/20 hover:scale-105"
                title="Buka informasi lengkap tentang aplikasi"
            >
                <span className="text-base">ℹ️</span>
                <span>Tentang Aplikasi</span>
            </a>

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
                        {/* Dropdown Periode */}
                        <select
                            value={periode}
                            onChange={handlePeriodeChange}
                            className="rounded-lg border-0 bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-red-500"
                        >
                            <option value="harian">📅 Harian</option>
                            <option value="mingguan">🗓️ Mingguan</option>
                            <option value="bulanan">📆 Bulanan</option>
                            <option value="semester"> Semester</option>
                        </select>

                        {/* ✅ BARU: Dropdown Filter Hari */}
                        <select
                            value={filterHari}
                            onChange={handleFilterHariChange}
                            className="rounded-lg border-0 bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-blue-500"
                            title="Filter data berdasarkan hari kejadian"
                        >
                            {hariOptions.map((hari) => (
                                <option key={hari} value={hari}>
                                    {hari === "Semua Hari"
                                        ? "👥 Semua Hari"
                                        : `📌 ${hari}`}
                                </option>
                            ))}
                        </select>

                        <button
                            onClick={downloadLaporan}
                            className="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-red-700"
                            title="Download PDF laporan sesuai periode & filter hari terpilih"
                        >
                            Download Laporan {labelPeriode[periode]}
                        </button>
                    </div>
                </div>
            </div>

            {/* ===== FILTER RENTANG TANGGAL ===== */}
            <div className="mb-6 rounded-lg bg-slate-800 p-4 shadow-lg">
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
                            <span className="text-sm text-slate-400">
                                Aktif:{" "}
                                <strong className="text-white">
                                    {currentFilters.dari_tanggal}
                                </strong>{" "}
                                s/d{" "}
                                <strong className="text-white">
                                    {currentFilters.sampai_tanggal}
                                </strong>
                                {filterHari !== "Semua Hari" && (
                                    <span className="ml-2 text-blue-400">
                                        • Filter: <strong>{filterHari}</strong>
                                    </span>
                                )}
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
                            <div>
                                <label className="block text-xs font-medium text-slate-400 mb-1">
                                    Filter Hari
                                </label>
                                <select
                                    value={filterHari}
                                    onChange={handleFilterHariChange}
                                    className="rounded-lg border-0 bg-slate-700 px-3 py-2 text-sm text-white focus:ring-2 focus:ring-indigo-500"
                                >
                                    {hariOptions.map((hari) => (
                                        <option key={hari} value={hari}>
                                            {hari === "Semua Hari"
                                                ? "👥 Semua Hari"
                                                : ` ${hari}`}
                                        </option>
                                    ))}
                                </select>
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
                                Reset
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <div className="space-y-6">
                {/* Absensi Petugas */}
                <KartuAbsensiPetugas
                    data={props.absensiPetugas ?? []}
                    displayKey={props.displayKey}
                    onDownloadDaftarHadir={downloadDaftarHadir}
                />

                <KartuStatistik stats={props.stats} />

                {/* 4 Panel Data Hari Ini */}
                <DataHariIni
                    keterlambatanList={props.keterlambatanList ?? []}
                    izinKeluarList={props.izinKeluarList ?? []}
                    pelanggaranList={props.pelanggaranList ?? []}
                    bukuTamuList={props.bukuTamuList ?? []}
                />

                {/* ===== GRAFIK 1: KETERLAMBATAN ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartData}
                            judul="📊 Keterlambatan per Kelas"
                        />
                    </div>
                    <DonutChart
                        data={props.donutJurusan}
                        judul=" Keterlambatan per Jurusan"
                    />
                </div>

                {/* ===== GRAFIK 2: IZIN KELUAR ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartIzinKelas ?? []}
                            judul="📊 Izin Keluar per Kelas"
                            warna="blue"
                        />
                    </div>
                    <DonutChart
                        data={props.donutIzinJurusan ?? []}
                        judul="🎓 Izin Keluar per Jurusan"
                    />
                </div>

                {/* ===== GRAFIK 3: PELANGGARAN ===== */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartPelanggaranKelas ?? []}
                            judul="⚠️ Pelanggaran per Kelas"
                            warna="orange"
                        />
                    </div>
                    <DonutChart
                        data={props.donutPelanggaranJurusan ?? []}
                        judul="🎓 Pelanggaran per Jurusan"
                    />
                </div>

                {/* Tabel Top & Aktivitas */}
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
