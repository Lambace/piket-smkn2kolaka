import { usePage } from "@inertiajs/react";
import { useState } from "react";

export default function KartuAbsensiPetugas({ data }) {
    // displayKey hanya ada di halaman TV → penentu route publik/auth
    const displayKey = usePage().props.displayKey ?? null;
    const [rekapOpen, setRekapOpen] = useState(false);

    // hadirList = semua status KECUALI alpha (termasuk sakit/izin/dl/lainnya)
    const hadirList = data?.filter((p) => p.status !== "alpha") ?? [];
    const alphaList = data?.filter((p) => p.status === "alpha") ?? [];

    // ===== BARU: Label dropdown lebih jelas =====
    const rekapOptions = [
        { value: "harian", label: "📅 Rekapan Harian" },
        { value: "mingguan", label: "📆 Rekapan Mingguan" },
        { value: "bulanan", label: "🗓️ Rekapan Bulanan" },
        { value: "semester", label: "🎓 Rekapan Semester" },
    ];

    // ===== Download Daftar Hadir (CHECKLIST — harian, tetap sama) =====
    const downloadDaftarHadir = () => {
        const today = new Date().toISOString().split("T")[0];
        const params = new URLSearchParams({
            periode: "harian",
            tanggal: today,
        });
        if (displayKey) params.set("k", displayKey);

        const url = displayKey
            ? route("tampil.daftar-hadir")
            : route("laporan.daftar-hadir");

        window.location.href = `${url}?${params.toString()}`;
    };

    // ===== Download REKAPAN (ANGKA) sesuai periode =====
    const downloadRekap = (periode) => {
        setRekapOpen(false);
        const today = new Date().toISOString().split("T")[0];
        const params = new URLSearchParams({
            periode,
            tanggal: today,
            semester: "ganjil",
            mode: "rekap", // ← BARU: mode angka
        });
        if (displayKey) params.set("k", displayKey);

        // ← BARU: route daftar-hadir (bukan laporan.pdf)
        const url = displayKey
            ? route("tampil.daftar-hadir")
            : route("laporan.daftar-hadir");

        window.location.href = `${url}?${params.toString()}`;
    };

    const getBadge = (status, jam) => {
        switch (status) {
            case "tepat_waktu":
                return {
                    className:
                        "bg-green-500/20 text-green-400 border border-green-500/30",
                    icon: "✅",
                    text: jam,
                };
            case "terlambat":
                return {
                    className:
                        "bg-amber-500/20 text-amber-400 border border-amber-500/30",
                    icon: "⏰",
                    text: jam,
                };
            case "sakit":
                return {
                    className:
                        "bg-purple-500/20 text-purple-400 border border-purple-500/30",
                    icon: "🤒",
                    text: "Sakit",
                };
            case "izin":
                return {
                    className:
                        "bg-yellow-500/20 text-yellow-400 border border-yellow-500/30",
                    icon: "📩",
                    text: "Izin",
                };
            case "dl":
                return {
                    className:
                        "bg-blue-500/20 text-blue-400 border border-blue-500/30",
                    icon: "🚗",
                    text: "DL",
                };
            case "lainnya":
                return {
                    className:
                        "bg-gray-500/20 text-gray-400 border border-gray-500/30",
                    icon: "📝",
                    text: "Lainnya",
                };
            default:
                return { className: "", icon: "", text: "" };
        }
    };

    // ===== Tooltip keterangan (untuk sakit/izin/dl/lainnya) =====
    const getTooltip = (p) => {
        const statusButuhKet = ["sakit", "izin", "dl", "lainnya"];
        return statusButuhKet.includes(p.status) && p.keterangan
            ? `Keterangan: ${p.keterangan}`
            : null;
    };

    return (
        <div className="rounded-xl border border-slate-700 bg-slate-800 p-5 shadow-lg">
            {/* ===== HEADER + TOMBOL (SELALU MUNCUL) ===== */}
            <div className="mb-4 flex flex-wrap items-center justify-between gap-2 border-b border-slate-700 pb-3">
                <h3 className="text-lg font-bold text-white">
                    🧑‍🏫 Petugas Piket Hari Ini
                </h3>

                <div className="flex items-center gap-2">
                    {/* Tombol Daftar Hadir (checklist harian) */}
                    <button
                        onClick={downloadDaftarHadir}
                        className="flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:bg-blue-700"
                        title="Download daftar hadir harian (checklist ✓)"
                    >
                        📋 Daftar Hadir
                    </button>

                    {/* Dropdown Rekap (angka per periode) */}
                    <div className="relative">
                        <button
                            onClick={() => setRekapOpen(!rekapOpen)}
                            className="flex items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white shadow transition hover:bg-emerald-700"
                            title="Download rekapan daftar hadir (angka) per periode"
                        >
                            📥 Rekap
                        </button>

                        {rekapOpen && (
                            <>
                                {/* Klik di luar → tutup */}
                                <div
                                    className="fixed inset-0 z-10"
                                    onClick={() => setRekapOpen(false)}
                                />
                                <div className="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-black/10">
                                    <div className="bg-slate-100 px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                        Pilih Periode Rekap
                                    </div>
                                    {rekapOptions.map((o) => (
                                        <button
                                            key={o.value}
                                            onClick={() =>
                                                downloadRekap(o.value)
                                            }
                                            className="block w-full px-4 py-2.5 text-left text-xs font-semibold text-gray-700 transition hover:bg-emerald-50 hover:text-emerald-700"
                                        >
                                            {o.label}
                                        </button>
                                    ))}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* ===== BELUM ADA DATA SAMA SEKALI ===== */}
            {!data || data.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-8 text-center">
                    <span className="mb-2 text-4xl">📭</span>
                    <p className="text-sm font-semibold text-slate-300">
                        Belum ada data absensi hari ini
                    </p>
                    <p className="mt-1 text-xs text-slate-500">
                        Kartu akan terisi otomatis setelah petugas melakukan
                        absen masuk.
                    </p>
                </div>
            ) : (
                <div className="space-y-4">
                    {/* ===== PERINGATAN: 0 HADIR + ADA ALPHA ===== */}
                    {hadirList.length === 0 && alphaList.length > 0 && (
                        <div className="flex items-center gap-3 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3">
                            <span className="text-xl">⚠️</span>
                            <p className="text-sm text-amber-300">
                                <strong>Belum ada petugas yang hadir.</strong>{" "}
                                {alphaList.length} petugas tercatat tidak hadir
                                (alpha).
                            </p>
                        </div>
                    )}

                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        {/* ===== KOLOM KIRI: TERDAFTAR ===== */}
                        <div className="rounded-lg border border-slate-700 bg-slate-900/50 p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h4 className="flex items-center gap-2 text-sm font-bold text-green-400">
                                    <span>✅</span>
                                    <span>Terdaftar</span>
                                </h4>
                                <span className="rounded-full bg-green-500/20 px-2.5 py-0.5 text-xs font-bold text-green-400">
                                    {hadirList.length} orang
                                </span>
                            </div>

                            {hadirList.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-6 text-center">
                                    <span className="mb-2 text-3xl">🕐</span>
                                    <p className="text-sm font-semibold text-slate-400">
                                        Belum ada petugas yang terdaftar
                                    </p>
                                    <p className="mt-0.5 text-xs text-slate-500">
                                        Menunggu absensi hari ini…
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {hadirList.map((p, i) => {
                                        const badge = getBadge(p.status, p.jam);
                                        const tooltip = getTooltip(p);
                                        return (
                                            <div
                                                key={i}
                                                className="flex items-center justify-between rounded-lg bg-slate-700/60 px-3 py-2.5"
                                                title={tooltip}
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-semibold text-white">
                                                        {p.nama}
                                                    </p>
                                                    <p className="truncate text-xs text-slate-400">
                                                        {p.jabatan}
                                                        {p.keterangan && (
                                                            <span className="ml-1 italic text-slate-500">
                                                                — "
                                                                {p.keterangan}"
                                                            </span>
                                                        )}
                                                    </p>
                                                </div>
                                                <span
                                                    className={`ml-2 shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${badge.className}`}
                                                >
                                                    {badge.icon} {badge.text}
                                                </span>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        {/* ===== KOLOM KANAN: ALPHA ===== */}
                        <div className="rounded-lg border border-red-900/50 bg-red-950/30 p-4">
                            <div className="mb-3 flex items-center justify-between">
                                <h4 className="flex items-center gap-2 text-sm font-bold text-red-400">
                                    <span>❌</span>
                                    <span>Tidak Hadir (Alpha)</span>
                                </h4>
                                <span className="rounded-full bg-red-500/20 px-2.5 py-0.5 text-xs font-bold text-red-400">
                                    {alphaList.length} orang
                                </span>
                            </div>

                            {alphaList.length === 0 ? (
                                <div className="flex flex-col items-center justify-center py-6 text-center">
                                    <span className="mb-2 text-3xl">🎉</span>
                                    <p className="text-sm font-semibold text-green-400">
                                        Semua petugas terdaftar!
                                    </p>
                                    <p className="mt-0.5 text-xs text-slate-500">
                                        Tidak ada yang alpha hari ini
                                    </p>
                                </div>
                            ) : (
                                <div className="space-y-2">
                                    {alphaList.map((p, i) => (
                                        <div
                                            key={i}
                                            className="flex items-center justify-between rounded-lg bg-red-900/40 px-3 py-2.5"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-semibold text-white">
                                                    {p.nama}
                                                </p>
                                                <p className="truncate text-xs text-slate-400">
                                                    {p.jabatan}
                                                </p>
                                            </div>
                                            <span className="ml-2 shrink-0 animate-pulse rounded-full border border-red-500/30 bg-red-500/20 px-2.5 py-1 text-xs font-bold text-red-400">
                                                ❌ ALPHA
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
