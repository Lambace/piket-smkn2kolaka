import { Head, usePoll, usePage } from "@inertiajs/react";
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

// --- ICON COMPONENTS (Menggunakan SVG sebaris agar langsung jalan) ---
const IconBell = () => (
    <svg
        className="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
        />
    </svg>
);
const IconHome = () => (
    <svg className="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
        <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.06 1.06l8.69-8.69z" />
        <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
    </svg>
);
const IconDocument = () => (
    <svg
        className="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
    </svg>
);
const IconChart = () => (
    <svg
        className="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
        />
    </svg>
);
const IconUsers = () => (
    <svg
        className="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
        />
    </svg>
);
const IconLogout = () => (
    <svg
        className="w-6 h-6"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth={2}
            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
        />
    </svg>
);

const SidebarItem = ({ active, children }) => (
    <div
        className={`w-12 h-12 flex items-center justify-center rounded-[14px] cursor-pointer transition-all duration-300 ${active ? "bg-white text-[#674FA3] shadow-md" : "text-white/60 hover:text-white hover:bg-white/10"}`}
    >
        {children}
    </div>
);

export default function Tampil(props) {
    usePoll(60000);

    const pengaturan = usePage().props.pengaturan ?? {};
    const [now, setNow] = useState(new Date());
    const [periode, setPeriode] = useState("harian");

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

    const downloadDaftarHadir = () => {
        const params = new URLSearchParams({
            periode: "harian",
            tanggal: today,
        });
        if (props.displayKey) params.set("k", props.displayKey);
        window.location.href = `${route("tampil.daftar-hadir")}?${params.toString()}`;
    };

    return (
        <div className="min-h-screen bg-[#DDE2EC] p-4 md:p-6 flex items-center justify-center font-sans text-slate-800">
            <Head title="Papan Informasi Piket" />

            {/* Tombol Tentang Aplikasi (Floating) */}
            <a
                href={route("papan.informasi")}
                target="_blank"
                rel="noopener noreferrer"
                className="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 flex items-center gap-2 rounded-full border border-white/40 bg-white/60 px-6 py-2.5 text-sm font-semibold text-[#674FA3] shadow-[0_8px_30px_rgb(0,0,0,0.1)] backdrop-blur-md transition hover:bg-white/90 hover:scale-105"
            >
                <span className="text-lg">ℹ️</span>
                <span>Tentang Aplikasi Piket</span>
            </a>

            {/* Kontainer Utama Aplikasi (Glassmorphism) */}
            <div className="bg-[#F4F6F9] w-full max-w-[1500px] h-[92vh] min-h-[800px] rounded-[40px] shadow-[0_20px_60px_rgba(103,79,163,0.15)] border-[8px] border-white/50 flex p-5 gap-6 overflow-hidden">
                {/* 1. Sidebar Kiri (Ungu Gelap) */}
                <div className="w-[85px] bg-[#674FA3] rounded-[30px] flex flex-col items-center py-8 justify-between shadow-lg shrink-0 z-10 relative overflow-hidden">
                    <div className="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-white/10 to-transparent opacity-50 pointer-events-none"></div>

                    <div className="flex flex-col gap-5 items-center z-10">
                        <div className="mb-4 text-white/80 hover:text-white cursor-pointer transition">
                            <IconBell />
                        </div>
                        <SidebarItem active={true}>
                            <IconHome />
                        </SidebarItem>
                        <SidebarItem>
                            <IconDocument />
                        </SidebarItem>
                        <SidebarItem>
                            <IconChart />
                        </SidebarItem>
                        <SidebarItem>
                            <IconUsers />
                        </SidebarItem>
                        <SidebarItem>
                            <svg
                                className="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
                                />
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                />
                            </svg>
                        </SidebarItem>
                    </div>

                    <div className="z-10">
                        <SidebarItem>
                            <IconLogout />
                        </SidebarItem>
                    </div>
                </div>

                {/* 2. Area Konten Utama Tengah */}
                <div className="flex-1 flex flex-col h-full overflow-y-auto pr-3 pb-16 scrollbar-hide">
                    {/* Header */}
                    <div className="flex justify-between items-end mt-2 mb-8 px-2">
                        <div>
                            <h2 className="text-sm font-bold text-[#674FA3] uppercase tracking-wider mb-1">
                                Pusat Informasi
                            </h2>
                            <h1 className="text-3xl font-extrabold text-slate-800">
                                Dashboard Piket
                            </h1>
                            <p className="text-sm font-medium text-slate-500 mt-1">
                                {pengaturan.nama_sekolah ?? "SMKN 2 Kolaka"} •{" "}
                                {props.hariIni}
                            </p>
                        </div>

                        <div className="flex flex-col items-end gap-3">
                            <div className="flex items-center gap-3 bg-white px-5 py-2.5 rounded-2xl shadow-sm border border-slate-100">
                                <div className="text-right">
                                    <div className="font-bold text-slate-700 text-lg">
                                        {now.toLocaleTimeString("id-ID", {
                                            hour: "2-digit",
                                            minute: "2-digit",
                                        })}
                                    </div>
                                </div>
                                <div className="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center overflow-hidden border-2 border-white shadow-sm">
                                    {logoSrc ? (
                                        <img
                                            src={logoSrc}
                                            alt="Logo"
                                            className="w-full h-full object-cover"
                                        />
                                    ) : (
                                        <span className="text-xl">🏫</span>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Baris Atas: Grafik Utama & Kartu Kontrol */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                        {/* Grafik Overview Besar (Ungu) */}
                        <div className="lg:col-span-2 bg-[#674FA3] rounded-[30px] p-6 text-white shadow-[0_15px_30px_rgba(103,79,163,0.25)] flex flex-col relative overflow-hidden">
                            <div className="absolute -top-24 -right-24 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl"></div>
                            <div className="absolute top-10 left-10 w-32 h-32 bg-[#FF789E] opacity-10 rounded-full blur-2xl"></div>

                            <div className="flex justify-between items-center mb-6 z-10 relative">
                                <div>
                                    <h2 className="text-xl font-bold">
                                        Overview Keterlambatan
                                    </h2>
                                    <p className="text-sm text-white/70">
                                        Grafik Keterlambatan per Kelas
                                    </p>
                                </div>
                                <div className="bg-white/10 px-4 py-1.5 rounded-full text-sm font-medium border border-white/20 backdrop-blur-sm">
                                    Bulan Ini ⬇
                                </div>
                            </div>

                            <div className="flex-1 bg-white/5 rounded-2xl p-4 backdrop-blur-sm border border-white/10 z-10 relative overflow-hidden">
                                <GrafikKeterlambatan
                                    data={props.chartData}
                                    judul=""
                                />
                            </div>
                        </div>

                        {/* Kartu Tumpuk Kanan (Petugas & Download) */}
                        <div className="flex flex-col gap-6">
                            {/* Kartu Petugas Piket (Ungu Terang) */}
                            <div className="flex-1 bg-[#856ec4] rounded-[30px] p-6 text-white flex flex-col shadow-[0_10px_20px_rgba(103,79,163,0.15)] relative overflow-hidden">
                                <div className="absolute -bottom-10 -right-10 w-32 h-32 bg-white opacity-10 rounded-full"></div>
                                <div className="z-10 relative w-full h-full flex flex-col">
                                    <h3 className="font-bold text-lg mb-3">
                                        Petugas Piket
                                    </h3>
                                    <div className="flex-1 bg-black/10 rounded-2xl p-3 overflow-y-auto scrollbar-hide">
                                        <KartuAbsensiPetugas
                                            data={props.absensiPetugas ?? []}
                                            displayKey={props.displayKey}
                                            onDownloadDaftarHadir={
                                                downloadDaftarHadir
                                            }
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Kartu Laporan (Gradient Pink) */}
                            <div className="flex-1 bg-gradient-to-br from-[#FF789E] to-[#FF98B8] rounded-[30px] p-6 text-white flex flex-col justify-center shadow-[0_10px_20px_rgba(255,120,158,0.25)] relative overflow-hidden">
                                <div className="absolute top-0 right-0 w-40 h-40 bg-white opacity-20 rounded-full blur-xl translate-x-10 -translate-y-10"></div>
                                <div className="z-10 relative">
                                    <h3 className="font-bold text-xl mb-1">
                                        Laporan Piket
                                    </h3>
                                    <p className="text-sm text-white/80 mb-4">
                                        Unduh rekap data
                                    </p>

                                    <div className="flex flex-col gap-3">
                                        <select
                                            value={periode}
                                            onChange={(e) =>
                                                setPeriode(e.target.value)
                                            }
                                            className="w-full rounded-2xl border-0 bg-white/20 text-white font-semibold focus:ring-2 focus:ring-white/50 backdrop-blur-md p-3 outline-none [&>option]:text-slate-800"
                                        >
                                            <option value="harian">
                                                Harian
                                            </option>
                                            <option value="mingguan">
                                                Mingguan
                                            </option>
                                            <option value="bulanan">
                                                Bulanan
                                            </option>
                                            <option value="semester">
                                                Semester
                                            </option>
                                        </select>
                                        <button
                                            onClick={downloadLaporan}
                                            className="w-full bg-white text-[#FF789E] rounded-2xl py-3 font-bold shadow-lg hover:bg-slate-50 hover:scale-[1.02] transition-transform"
                                        >
                                            Download {labelPeriode[periode]}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Baris Tengah: 3 Kartu Aktivitas Putih */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100 flex flex-col">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="font-bold text-slate-700">
                                    Donut Keterlambatan
                                </h3>
                                <div className="w-10 h-10 rounded-2xl bg-[#674FA3] text-white flex items-center justify-center shadow-md">
                                    <IconChart />
                                </div>
                            </div>
                            <DonutChart data={props.donutJurusan} judul="" />
                        </div>
                        <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100 flex flex-col">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="font-bold text-slate-700">
                                    Donut Izin Keluar
                                </h3>
                                <div className="w-10 h-10 rounded-2xl bg-[#5289df] text-white flex items-center justify-center shadow-md">
                                    <IconChart />
                                </div>
                            </div>
                            <DonutChart
                                data={props.donutIzinJurusan ?? []}
                                judul=""
                            />
                        </div>
                        <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100 flex flex-col">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="font-bold text-slate-700">
                                    Donut Pelanggaran
                                </h3>
                                <div className="w-10 h-10 rounded-2xl bg-[#FF789E] text-white flex items-center justify-center shadow-md">
                                    <IconChart />
                                </div>
                            </div>
                            <DonutChart
                                data={props.donutPelanggaranJurusan ?? []}
                                judul=""
                            />
                        </div>
                    </div>

                    {/* Bagian Bawah: Data Sisa */}
                    <div className="space-y-6">
                        <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100">
                            <h3 className="font-bold text-slate-800 text-lg mb-4">
                                Statistik & Data Hari Ini
                            </h3>
                            <KartuStatistik stats={props.stats} />
                            <div className="mt-6">
                                <DataHariIni
                                    keterlambatanList={
                                        props.keterlambatanList ?? []
                                    }
                                    izinKeluarList={props.izinKeluarList ?? []}
                                    pelanggaranList={
                                        props.pelanggaranList ?? []
                                    }
                                    bukuTamuList={props.bukuTamuList ?? []}
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100">
                                <GrafikKeterlambatan
                                    data={props.chartIzinKelas ?? []}
                                    judul="📊 Izin Keluar per Kelas"
                                    warna="blue"
                                />
                            </div>
                            <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100">
                                <GrafikKeterlambatan
                                    data={props.chartPelanggaranKelas ?? []}
                                    judul="📊 Pelanggaran per Kelas"
                                    warna="orange"
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100">
                                <TabelTerlambatTertinggi
                                    data={props.topTerlambat}
                                />
                            </div>
                            <div className="bg-white rounded-[30px] p-6 shadow-[0_8px_20px_rgba(0,0,0,0.03)] border border-slate-100">
                                <TabelPoinTertinggi
                                    data={props.topPelanggaran}
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {/* 3. Sidebar Kanan (Aktivitas / "Friends" List style) */}
                <div className="w-[300px] bg-white rounded-[30px] p-5 shadow-[0_0_20px_rgba(0,0,0,0.02)] shrink-0 overflow-y-auto scrollbar-hide flex flex-col gap-6 hidden xl:flex border border-slate-50">
                    {/* Switcher */}
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="font-bold text-slate-800 text-lg">
                            Log Aktivitas
                        </h2>
                        <button className="text-xs font-bold text-[#674FA3]">
                            View All
                        </button>
                    </div>
                    <div className="flex bg-[#F4F6F9] rounded-full p-1 border border-slate-100">
                        <button className="flex-1 bg-[#674FA3] text-white rounded-full py-2 text-xs font-bold shadow-md">
                            Terbaru
                        </button>
                        <button className="flex-1 text-slate-500 rounded-full py-2 text-xs font-bold hover:bg-slate-200/50 transition">
                            Tamu
                        </button>
                    </div>

                    {/* List Aktivitas yang dialihkan ke Sidebar Kanan */}
                    <div className="flex-1">
                        <AktivitasTerbaru data={props.aktivitas} />
                    </div>

                    {/* Widget Peta Visual (Seperti "Live Map" di referensi gambar) */}
                    <div className="bg-[#F8F9FC] rounded-[24px] p-4 border border-slate-100">
                        <div className="flex justify-between items-center mb-3">
                            <h3 className="font-bold text-slate-700 text-sm flex items-center gap-2">
                                <svg
                                    className="w-4 h-4 text-slate-400"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                    />
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth={2}
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                    />
                                </svg>
                                Area Pantauan
                            </h3>
                            <button className="text-[10px] font-bold text-slate-400">
                                View
                            </button>
                        </div>
                        <div className="aspect-[4/3] bg-slate-200 rounded-2xl flex items-center justify-center relative overflow-hidden shadow-inner">
                            <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>

                            {/* Pin penanda visualisasi */}
                            <div className="w-10 h-10 rounded-full bg-[#FF789E]/20 flex items-center justify-center shadow-lg z-10 absolute top-[50%] left-[50%] animate-pulse translate-x-[-50%] translate-y-[-50%]">
                                <div className="w-6 h-6 rounded-full bg-[#FF789E] border-2 border-white flex items-center justify-center text-white text-[10px]">
                                    📍
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* CSS untuk menyembunyikan scrollbar agar UI terlihat rapi */}
            <style
                dangerouslySetInnerHTML={{
                    __html: `
                .scrollbar-hide::-webkit-scrollbar { display: none; }
                .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
            `,
                }}
            />
        </div>
    );
}
