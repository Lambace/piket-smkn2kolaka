import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router } from "@inertiajs/react";
import FilterTanggal from "./Dashboard/Components/FilterTanggal";
import KartuStatistik from "./Dashboard/Components/KartuStatistik";
import GrafikKeterlambatan from "./Dashboard/Components/GrafikKeterlambatan";
import RingkasanPiket from "./Dashboard/Components/RingkasanPiket";
import TabelPoinTertinggi from "./Dashboard/Components/TabelPoinTertinggi";
import TabelTerlambatTertinggi from "./Dashboard/Components/TabelTerlambatTertinggi";
import DistribusiPelanggaran from "./Dashboard/Components/DistribusiPelanggaran";
import KelasMelanggarTertinggi from "./Dashboard/Components/KelasMelanggarTertinggi"; // ← BARU
import DonutChart from "./Dashboard/Components/DonutChart";
import AktivitasTerbaru from "./Dashboard/Components/AktivitasTerbaru";

export default function Dashboard(props) {
    const params = props.params ?? {};

    const onGrafikFilter = (kelas, jurusan) => {
        router.get(
            route("dashboard"),
            {
                dari_tanggal: params.dari_tanggal,
                sampai_tanggal: params.sampai_tanggal,
                periode_grafik: params.periode_grafik,
                grafik_kelas: kelas || undefined,
                grafik_jurusan: jurusan || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-gray-800">
                            Dashboard Piket
                        </h2>
                        <p className="text-sm text-gray-500">{props.hariIni}</p>
                    </div>
                    <a
                        href={route("papan.informasi")}
                        className="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow transition hover:bg-sky-700"
                        title="Buka Papan Informasi Digital"
                    >
                        ℹ️ Tentang Aplikasi
                    </a>
                </div>
            }
        >
            <Head title="Dashboard" />

            <div className="space-y-6">
                <FilterTanggal params={params} />

                <div className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                    📅 Menampilkan data: <b>{props.rentangAktif}</b> • Grafik
                    keterlambatan: <b>per kelas</b> • Grafik pelanggaran:{" "}
                    <b>{params.periode_grafik ?? 7} hari</b> terakhir
                    {(params.grafik_kelas || params.grafik_jurusan) && (
                        <>
                            {" "}
                            • Filter grafik:{" "}
                            <b>{params.grafik_kelas ?? "Semua Kelas"}</b> /{" "}
                            <b>{params.grafik_jurusan ?? "Semua Jurusan"}</b>
                        </>
                    )}
                </div>

                <KartuStatistik stats={props.stats} />

                {/* Baris 1: Grafik keterlambatan PER KELAS + Donut jurusan */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartData}
                            judul="📊 Keterlambatan per Kelas"
                            filter={{
                                kelas: params.grafik_kelas ?? "",
                                jurusan: params.grafik_jurusan ?? "",
                                kelasOptions: props.kelasOptions ?? [],
                                jurusanOptions: props.jurusanOptions ?? [],
                                onChange: onGrafikFilter,
                            }}
                        />
                    </div>
                    <DonutChart
                        data={props.donutJurusan}
                        judul="🎓 Keterlambatan per Jurusan"
                    />
                </div>

                {/* Baris 2: Grafik pelanggaran per hari + Ringkasan */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-2">
                        <GrafikKeterlambatan
                            data={props.chartPelanggaran}
                            judul="📈 Tren Pelanggaran Harian"
                            warna="orange"
                        />
                    </div>
                    <RingkasanPiket
                        chartData={props.chartData}
                        chartPelanggaran={props.chartPelanggaran}
                    />
                </div>

                {/* BARIS BARU: Kelas Melanggar Tertinggi (ganti hari/jenis) */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div className="lg:col-span-1">
                        <KelasMelanggarTertinggi
                            data={props.kelasMelanggarTertinggi}
                        />
                    </div>
                    <DonutChart
                        data={props.donutStatusPelanggaran}
                        judul="🧩 Status Pelanggaran"
                    />
                    <AktivitasTerbaru data={props.aktivitas} />
                </div>

                {/* Baris berikutnya: Distribusi + Tabel top siswa */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <DistribusiPelanggaran data={props.jenisPelanggaran} />
                    <div className="lg:col-span-2">
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <TabelPoinTertinggi data={props.topPelanggaran} />
                            <TabelTerlambatTertinggi
                                data={props.topTerlambat}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
