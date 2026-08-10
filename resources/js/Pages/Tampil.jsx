import { Head, usePoll, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";
import KartuStatistik from "./Dashboard/Components/KartuStatistik";
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

    const [now, setNow] = useState(new Date());
    const [periode, setPeriode] = useState("bulanan");

    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);

    const today = new Date().toISOString().split("T")[0];

    // Semester otomatis: Jul-Des = Ganjil, Jan-Jun = Genap
    const semesterOtomatis =
        new Date().getMonth() + 1 >= 7 ? "ganjil" : "genap";

    const downloadLaporan = () => {
        const params = new URLSearchParams({
            jenis: "gabungan",
            periode,
            tanggal: today,
            semester: semesterOtomatis,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.laporan")}?${params.toString()}`;
    };

    return (
        <div className="min-h-screen bg-slate-900 p-6">
            <Head title="Papan Informasi Piket" />

            {/* Tombol Tentang Aplikasi - melayang kanan bawah */}
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

                    {/* Pemilih periode + tombol download */}
                    <div className="flex items-center gap-2">
                        <select
                            value={periode}
                            onChange={(e) => setPeriode(e.target.value)}
                            className="rounded-lg border-0 bg-slate-800 px-3 py-2 text-sm font-semibold text-white shadow-lg focus:ring-2 focus:ring-red-500"
                        >
                            <option value="harian">📅 Harian</option>
                            <option value="mingguan">🗓️ Mingguan</option>
                            <option value="bulanan">📆 Bulanan</option>
                            <option value="semester">🎓 Semester</option>
                        </select>
                        <button
                            onClick={downloadLaporan}
                            className="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-lg transition hover:bg-red-700"
                            title="Download PDF laporan sesuai periode terpilih"
                        >
                            <svg
                                className="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                                />
                            </svg>
                            📥 Download Laporan {labelPeriode[periode]}
                        </button>
                    </div>
                </div>
            </div>

            <div className="space-y-6">
                <KartuStatistik stats={props.stats} />

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
