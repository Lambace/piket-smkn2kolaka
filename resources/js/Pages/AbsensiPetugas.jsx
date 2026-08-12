import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";

const statusInfo = {
    tepat_waktu: {
        label: "Tepat Waktu",
        cls: "bg-green-100 text-green-700",
        icon: "✅",
    },
    terlambat: {
        label: "Terlambat",
        cls: "bg-amber-100 text-amber-700",
        icon: "⏰",
    },
    sakit: { label: "Sakit", cls: "bg-purple-100 text-purple-700", icon: "🤒" },
    izin: { label: "Izin", cls: "bg-yellow-100 text-yellow-700", icon: "📩" },
    dl: { label: "Dinas Luar", cls: "bg-blue-100 text-blue-700", icon: "🚗" },
    lainnya: { label: "Lainnya", cls: "bg-gray-100 text-gray-700", icon: "📝" },
};

const opsiLainnya = [
    { value: "sakit", label: "🤒 Sakit" },
    { value: "izin", label: "📩 Izin" },
    { value: "dl", label: "🚗 Dinas Luar (DL)" },
    { value: "lainnya", label: "📝 Lainnya" },
];

export default function AbsensiIndex({ absenHariIni, summary, riwayat }) {
    const { flash, auth } = usePage().props;
    const [now, setNow] = useState(new Date());
    const [dropOpen, setDropOpen] = useState(false);
    const [modalStatus, setModalStatus] = useState(null);
    const [keterangan, setKeterangan] = useState("");
    const [err, setErr] = useState("");

    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    const jam = now.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
    const tanggal = now.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });

    const absenMasuk = () =>
        router.post(route("absensi.store"), { status: "masuk" });

    const pilihStatus = (s) => {
        setDropOpen(false);
        setKeterangan("");
        setErr("");
        setModalStatus(s);
    };

    const kirimKeterangan = (e) => {
        e.preventDefault();
        if (keterangan.trim().length < 5) {
            setErr("Mohon isi keterangan halangan minimal 5 karakter.");
            return;
        }
        router.post(route("absensi.store"), {
            status: modalStatus,
            keterangan,
        });
    };

    const infoModal = opsiLainnya.find((o) => o.value === modalStatus);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    🕐 Absensi Petugas Piket
                </h2>
            }
        >
            <Head title="Absensi Piket" />

            <div className="mx-auto max-w-md space-y-4">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                        ✅ {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        ❌ {flash.error}
                    </div>
                )}

                {/* ===== HEADER TIM ===== */}
                <div className="rounded-2xl bg-slate-900 p-5 text-center text-white shadow-lg">
                    <div className="flex items-center justify-center gap-2">
                        <h3 className="text-lg font-extrabold">
                            TIM PIKET{" "}
                            {now
                                .toLocaleDateString("id-ID", {
                                    weekday: "long",
                                })
                                .toUpperCase()}
                        </h3>
                        <span className="rounded-full bg-slate-700 px-2.5 py-0.5 text-xs font-semibold capitalize">
                            {auth?.user?.role}
                        </span>
                        <span className="rounded-full bg-green-500 px-2.5 py-0.5 text-xs font-bold">
                            Online
                        </span>
                    </div>
                    <p className="mt-1 text-xs text-slate-400">
                        Sistem Informasi Piket
                    </p>
                    <div className="mt-3 flex items-center justify-center gap-2 rounded-full bg-slate-800 px-4 py-2 text-xs">
                        <span className="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-500 font-bold">
                            {auth?.user?.name?.charAt(0)}
                        </span>
                        <span className="font-semibold">
                            {auth?.user?.name}
                        </span>
                        <span className="text-slate-400">
                            • {auth?.user?.email}
                        </span>
                    </div>
                </div>

                {/* ===== KARTU RINGKASAN KEHADIRAN ===== */}
                <div className="rounded-2xl bg-white p-4 text-center shadow">
                    <h4 className="font-bold text-gray-800">Kehadiran</h4>
                    <p className="mt-1 text-sm text-gray-600">
                        {summary?.hadir ?? 0} Hari Hadir | Izin{" "}
                        {summary?.izin ?? 0} Hari | Sakit {summary?.sakit ?? 0}{" "}
                        | DL {summary?.dl ?? 0}
                    </p>
                    <p className="mt-0.5 text-xs text-gray-400">
                        Periode bulan ini
                    </p>
                </div>

                {/* ===== KARTU AKSI ABSEN (hijau) ===== */}
                {!absenHariIni ? (
                    <div className="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-lg">
                        <p className="mb-4 text-center font-bold">{tanggal}</p>

                        <div className="flex items-center justify-center gap-3">
                            {/* Tombol MASUK — langsung simpan */}
                            <button
                                onClick={absenMasuk}
                                className="flex flex-col items-center rounded-full bg-green-600 px-8 py-3 shadow-lg transition hover:scale-105 hover:bg-green-700"
                                title="Klik untuk absen masuk — langsung tersimpan"
                            >
                                <span className="font-mono text-lg font-extrabold">
                                    🕐 {jam} WITA
                                </span>
                                <span className="text-sm font-semibold">
                                    Masuk
                                </span>
                            </button>

                            {/* Dropdown LAINNYA — wajib keterangan */}
                            <div className="relative">
                                <button
                                    onClick={() => setDropOpen(!dropOpen)}
                                    className="rounded-full bg-white px-5 py-3 text-sm font-bold text-teal-700 shadow-lg transition hover:bg-teal-50"
                                >
                                    Lainnya ▾
                                </button>
                                {dropOpen && (
                                    <div className="absolute right-0 z-20 mt-2 w-40 overflow-hidden rounded-xl bg-white text-gray-700 shadow-xl">
                                        {opsiLainnya.map((o) => (
                                            <button
                                                key={o.value}
                                                onClick={() =>
                                                    pilihStatus(o.value)
                                                }
                                                className="block w-full px-4 py-2.5 text-left text-sm hover:bg-teal-50"
                                            >
                                                {o.label}
                                            </button>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>

                        <p className="mt-4 text-center text-xs text-emerald-100">
                            Klik <b>Masuk</b> = langsung tersimpan •{" "}
                            <b>Lainnya</b> = wajib isi keterangan halangan
                        </p>
                    </div>
                ) : (
                    /* ===== SUDAH ABSEN HARI INI ===== */
                    <div className="rounded-2xl bg-white p-5 text-center shadow">
                        <p className="text-sm text-gray-500">
                            Anda sudah absen hari ini
                        </p>
                        <div className="mt-2 flex items-center justify-center gap-2">
                            <span
                                className={`rounded-full px-4 py-1.5 text-sm font-bold ${
                                    statusInfo[absenHariIni.status]?.cls
                                }`}
                            >
                                {statusInfo[absenHariIni.status]?.icon}{" "}
                                {statusInfo[absenHariIni.status]?.label}
                            </span>
                            {absenHariIni.jam_masuk && (
                                <span className="font-mono text-lg font-bold text-gray-800">
                                    {absenHariIni.jam_masuk.slice(0, 5)}
                                </span>
                            )}
                        </div>
                        {absenHariIni.keterangan && (
                            <p className="mt-2 rounded-lg bg-gray-50 p-2 text-xs italic text-gray-600">
                                “{absenHariIni.keterangan}”
                            </p>
                        )}
                    </div>
                )}

                {/* ===== RIWAYAT ABSENSI ===== */}
                <div className="rounded-2xl bg-white p-4 shadow">
                    <h4 className="mb-3 font-bold text-gray-800">
                        📜 Riwayat Absensi Terakhir
                    </h4>
                    <div className="space-y-2">
                        {!riwayat || riwayat.length === 0 ? (
                            <p className="py-4 text-center text-sm text-gray-400">
                                Belum ada riwayat absensi.
                            </p>
                        ) : (
                            riwayat.map((r) => (
                                <div
                                    key={r.id}
                                    className="flex items-center justify-between rounded-lg border border-gray-100 bg-gray-50 px-3 py-2.5"
                                >
                                    <div>
                                        <p className="text-sm font-semibold text-gray-800">
                                            {new Date(
                                                r.tanggal,
                                            ).toLocaleDateString("id-ID", {
                                                day: "numeric",
                                                month: "long",
                                                year: "numeric",
                                            })}
                                        </p>
                                        {r.keterangan && (
                                            <p className="text-xs italic text-gray-500">
                                                “{r.keterangan}”
                                            </p>
                                        )}
                                    </div>
                                    <span
                                        className={`rounded-full px-2.5 py-1 text-xs font-bold ${
                                            statusInfo[r.status]?.cls
                                        }`}
                                    >
                                        {statusInfo[r.status]?.icon}{" "}
                                        {r.jam_masuk
                                            ? r.jam_masuk.slice(0, 5)
                                            : statusInfo[r.status]?.label}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>

            {/* ===== MODAL KETERANGAN HALANGAN ===== */}
            {modalStatus && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <form
                        onSubmit={kirimKeterangan}
                        className="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl"
                    >
                        <h3 className="mb-1 font-bold text-gray-800">
                            {infoModal?.label}
                        </h3>
                        <p className="mb-3 text-xs text-gray-500">
                            Wajib mengisi keterangan halangan untuk status ini.
                        </p>
                        <textarea
                            value={keterangan}
                            onChange={(e) => setKeterangan(e.target.value)}
                            rows={4}
                            autoFocus
                            placeholder="Contoh: Sakit demam sejak semalam, surat susul besok…"
                            className="w-full rounded-lg border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                        />
                        {err && (
                            <p className="mt-1 text-xs text-red-600">
                                ❌ {err}
                            </p>
                        )}
                        <div className="mt-4 flex gap-2">
                            <button
                                type="button"
                                onClick={() => setModalStatus(null)}
                                className="flex-1 rounded-lg bg-gray-200 px-3 py-2 text-sm font-semibold text-gray-700"
                            >
                                Batal
                            </button>
                            <button className="flex-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-bold text-white hover:bg-teal-700">
                                💾 Simpan
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
