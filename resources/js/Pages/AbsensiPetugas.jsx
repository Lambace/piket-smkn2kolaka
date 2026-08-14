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
    alpha: { label: "Alpha", cls: "bg-red-100 text-red-700", icon: "❌" },
};

const opsiLainnya = [
    { value: "sakit", label: "🤒 Sakit" },
    { value: "izin", label: "📩 Izin" },
    { value: "dl", label: "🚗 Dinas Luar (DL)" },
    { value: "lainnya", label: "📝 Lainnya" },
];

const opsiEdit = [
    { value: "tepat_waktu", label: "✅ Tepat Waktu" },
    { value: "terlambat", label: "⏰ Terlambat" },
    { value: "sakit", label: "🤒 Sakit" },
    { value: "izin", label: "📩 Izin" },
    { value: "dl", label: "🚗 Dinas Luar" },
    { value: "lainnya", label: "📝 Lainnya" },
    { value: "alpha", label: "❌ Alpha (hapus sebagai hadir)" },
];

// ===== BARU: Hitung jarak Haversine di frontend (meter) =====
const haversine = (lat1, lng1, lat2, lng2) => {
    const R = 6371000;
    const toRad = (x) => (x * Math.PI) / 180;
    const dLat = toRad(lat2 - lat1);
    const dLng = toRad(lng2 - lng1);
    const a =
        Math.sin(dLat / 2) ** 2 +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
};

export default function AbsensiIndex({
    absenHariIni,
    summary,
    riwayat,
    semuaPetugas = [],
    isKoordinator = false,
    geofence = { aktif: false }, // ← BARU
}) {
    const { flash, auth } = usePage().props;
    const [now, setNow] = useState(new Date());
    const [dropOpen, setDropOpen] = useState(false);
    const [modalStatus, setModalStatus] = useState(null);
    const [keterangan, setKeterangan] = useState("");
    const [err, setErr] = useState("");

    const [editModal, setEditModal] = useState(null);
    const [editForm, setEditForm] = useState({
        status: "",
        jam_masuk: "",
        keterangan: "",
    });

    // ===== BARU: State geofencing =====
    const [geo, setGeo] = useState({
        status: "loading", // loading | ok | far | denied | error
        lat: null,
        lng: null,
        accuracy: null,
        jarakMeter: null,
        pesan: "",
    });

    // Jam & tanggal live
    useEffect(() => {
        const t = setInterval(() => setNow(new Date()), 1000);
        return () => clearInterval(t);
    }, []);

    // ===== BARU: Ambil GPS saat mount =====
    useEffect(() => {
        if (!geofence.aktif) {
            setGeo((g) => ({
                ...g,
                status: "off",
                pesan: "Geofence tidak aktif",
            }));
            return;
        }

        if (!("geolocation" in navigator)) {
            setGeo((g) => ({
                ...g,
                status: "error",
                pesan: "Browser tidak mendukung GPS. Gunakan Chrome/Edge terbaru.",
            }));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const { latitude, longitude, accuracy } = pos.coords;
                const jarak = haversine(
                    latitude,
                    longitude,
                    geofence.lat,
                    geofence.lng,
                );
                const jarakBulat = Math.round(jarak);
                const dekat = jarakBulat <= geofence.radius_meter;
                const sinyalOk = accuracy <= 500;

                if (!sinyalOk) {
                    setGeo({
                        status: "error",
                        lat: latitude,
                        lng: longitude,
                        accuracy,
                        jarakMeter: jarakBulat,
                        pesan: `Sinyal GPS lemah (akurasi ${Math.round(accuracy)} m). Pindah ke tempat terbuka.`,
                    });
                } else if (dekat) {
                    setGeo({
                        status: "ok",
                        lat: latitude,
                        lng: longitude,
                        accuracy,
                        jarakMeter: jarakBulat,
                        pesan: `Anda berada ${jarakBulat} m dari sekolah ✓`,
                    });
                } else {
                    setGeo({
                        status: "far",
                        lat: latitude,
                        lng: longitude,
                        accuracy,
                        jarakMeter: jarakBulat,
                        pesan: `Anda ${jarakBulat} m dari sekolah (maks ${geofence.radius_meter} m).`,
                    });
                }
            },
            (error) => {
                let pesan = "Gagal membaca lokasi.";
                if (error.code === 1)
                    pesan =
                        "Izin lokasi ditolak. Aktifkan di pengaturan browser.";
                else if (error.code === 2)
                    pesan = "Posisi tidak tersedia. Pastikan GPS aktif.";
                else if (error.code === 3)
                    pesan = "Timeout membaca GPS. Coba lagi.";
                setGeo((g) => ({ ...g, status: "denied", pesan }));
            },
            { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
    }, [geofence]);

    const jam = now.toLocaleTimeString("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
    });
    const tanggal = now.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "long",
        year: "numeric",
    });

    // ===== BARU: Absen masuk dengan lokasi =====
    const absenMasuk = () => {
        if (geofence.aktif && geo.status !== "ok") {
            setErr(
                geo.status === "far"
                    ? `Absen ditolak: ${geo.pesan}`
                    : geo.status === "denied" || geo.status === "error"
                      ? geo.pesan
                      : "Menunggu GPS… coba beberapa detik lagi.",
            );
            return;
        }

        router.post(
            route("absensi.store"),
            {
                status: "masuk",
                lat: geo.lat,
                lng: geo.lng,
                accuracy: geo.accuracy,
            },
            { onSuccess: () => router.visit(route("dashboard")) },
        );
    };

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
        router.post(
            route("absensi.store"),
            {
                status: modalStatus,
                keterangan,
                // Status non-masuk tidak kirim lokasi (privasi)
                lat: null,
                lng: null,
                accuracy: null,
            },
            {
                onSuccess: () => {
                    setModalStatus(null);
                    setKeterangan("");
                    setErr("");
                    router.visit(route("dashboard"));
                },
            },
        );
    };

    const bukaEdit = (p) => {
        setEditModal(p);
        setEditForm({
            status: p.status === "alpha" ? "tepat_waktu" : p.status,
            jam_masuk: p.jam_masuk ? p.jam_masuk.slice(0, 5) : "",
            keterangan: p.keterangan || "",
        });
    };

    const simpanEdit = (e) => {
        e.preventDefault();
        router.put(
            route("absensi-petugas.update", editModal.absen_id),
            editForm,
            {
                onSuccess: () => setEditModal(null),
            },
        );
    };

    const hapusAbsen = (p) => {
        if (!confirm(`Hapus absensi ${p.nama}?`)) return;
        router.delete(route("absensi-petugas.destroy", p.absen_id));
    };

    const bolehEdit = (p) => isKoordinator || p.nama === auth?.user?.name;
    const infoModal = opsiLainnya.find((o) => o.value === modalStatus);
    const sudahAbsen = semuaPetugas.filter((p) => p.sudah_absen).length;
    const belumAbsen = semuaPetugas.length - sudahAbsen;

    // ===== BARU: Warna kartu lokasi =====
    const geoStyle = {
        ok: {
            bg: "bg-green-50 border-green-200",
            text: "text-green-700",
            icon: "🟢",
        },
        far: {
            bg: "bg-red-50 border-red-200",
            text: "text-red-700",
            icon: "🔴",
        },
        denied: {
            bg: "bg-yellow-50 border-yellow-200",
            text: "text-yellow-700",
            icon: "⚠️",
        },
        error: {
            bg: "bg-yellow-50 border-yellow-200",
            text: "text-yellow-700",
            icon: "⚠️",
        },
        loading: {
            bg: "bg-slate-50 border-slate-200",
            text: "text-slate-500",
            icon: "⏳",
        },
        off: {
            bg: "bg-slate-50 border-slate-200",
            text: "text-slate-500",
            icon: "🔕",
        },
    }[geo.status] || {
        bg: "bg-slate-50 border-slate-200",
        text: "text-slate-500",
        icon: "❓",
    };

    const tombolTerkunci = geofence.aktif && geo.status !== "ok";

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    🕐 Absensi Petugas Piket
                </h2>
            }
        >
            <Head title="Absensi Piket" />

            <div className="rounded-3xl bg-gradient-to-br from-slate-200 via-slate-100 to-teal-100 p-4 sm:p-6 lg:p-8">
                <div className="mx-auto max-w-5xl">
                    {flash?.success && (
                        <div className="mb-3 rounded-xl border border-green-200 bg-green-50 p-3 text-sm text-green-800 shadow-sm">
                            ✅ {flash.success}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-3 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 shadow-sm">
                            ❌ {flash.error}
                        </div>
                    )}

                    <div className="overflow-hidden rounded-[1.75rem] bg-white shadow-2xl ring-1 ring-slate-900/10">
                        <div className="bg-slate-900 px-6 py-5 text-white">
                            <div className="flex flex-col items-center gap-3 md:flex-row md:justify-between">
                                <div className="text-center md:text-left">
                                    <div className="flex flex-wrap items-center justify-center gap-2 md:justify-start">
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
                                </div>
                                <div className="flex items-center gap-2 rounded-full bg-slate-800 px-4 py-2 text-xs">
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
                        </div>

                        <div className="grid gap-4 bg-slate-50 p-4 lg:grid-cols-5 lg:p-6">
                            <div className="space-y-4 lg:col-span-2">
                                <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5">
                                    <h4 className="text-center font-bold text-gray-800">
                                        Kehadiran
                                    </h4>
                                    <div className="mt-3 grid grid-cols-4 gap-2 text-center">
                                        <div className="rounded-xl bg-green-50 p-2">
                                            <p className="text-xl font-extrabold text-green-600">
                                                {summary?.hadir ?? 0}
                                            </p>
                                            <p className="text-[10px] font-semibold text-green-700">
                                                Hadir
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-yellow-50 p-2">
                                            <p className="text-xl font-extrabold text-yellow-600">
                                                {summary?.izin ?? 0}
                                            </p>
                                            <p className="text-[10px] font-semibold text-yellow-700">
                                                Izin
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-purple-50 p-2">
                                            <p className="text-xl font-extrabold text-purple-600">
                                                {summary?.sakit ?? 0}
                                            </p>
                                            <p className="text-[10px] font-semibold text-purple-700">
                                                Sakit
                                            </p>
                                        </div>
                                        <div className="rounded-xl bg-blue-50 p-2">
                                            <p className="text-xl font-extrabold text-blue-600">
                                                {summary?.dl ?? 0}
                                            </p>
                                            <p className="text-[10px] font-semibold text-blue-700">
                                                DL
                                            </p>
                                        </div>
                                    </div>
                                    <p className="mt-2 text-center text-[10px] text-gray-400">
                                        Periode bulan ini
                                    </p>
                                </div>

                                {/* ===== BARU: Kartu Status Lokasi ===== */}
                                {geofence.aktif && !absenHariIni && (
                                    <div
                                        className={`rounded-2xl border-2 p-4 transition ${geoStyle.bg}`}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span className="text-3xl">
                                                {geoStyle.icon}
                                            </span>
                                            <div className="flex-1">
                                                <h5
                                                    className={`text-sm font-bold ${geoStyle.text}`}
                                                >
                                                    Verifikasi Lokasi
                                                </h5>
                                                <p
                                                    className={`mt-1 text-xs ${geoStyle.text}`}
                                                >
                                                    {geo.pesan}
                                                </p>
                                                {geo.accuracy &&
                                                    geo.status !== "denied" && (
                                                        <p className="mt-1 text-[10px] text-gray-500">
                                                            Akurasi GPS: ±
                                                            {Math.round(
                                                                geo.accuracy,
                                                            )}{" "}
                                                            m
                                                        </p>
                                                    )}
                                                {(geo.status === "denied" ||
                                                    geo.status === "error") && (
                                                    <button
                                                        onClick={() =>
                                                            window.location.reload()
                                                        }
                                                        className="mt-2 rounded-lg bg-white px-3 py-1 text-[11px] font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                                    >
                                                        🔄 Coba Lagi
                                                    </button>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {!absenHariIni ? (
                                    <div className="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white shadow-lg">
                                        <p className="mb-4 text-center font-bold">
                                            {tanggal}
                                        </p>
                                        <div className="flex flex-col items-center justify-center gap-3 sm:flex-row">
                                            <button
                                                onClick={absenMasuk}
                                                disabled={tombolTerkunci}
                                                className={`flex w-full flex-col items-center rounded-2xl px-6 py-3 shadow-lg transition sm:w-auto sm:rounded-full sm:px-8 ${
                                                    tombolTerkunci
                                                        ? "cursor-not-allowed bg-gray-500 opacity-60"
                                                        : "bg-green-600 hover:scale-105 hover:bg-green-700"
                                                }`}
                                                title={
                                                    tombolTerkunci
                                                        ? "Anda di luar area sekolah"
                                                        : "Absen sekarang"
                                                }
                                            >
                                                <span className="font-mono text-lg font-extrabold">
                                                    {tombolTerkunci
                                                        ? "🔒"
                                                        : "🕐"}{" "}
                                                    {jam} WITA
                                                </span>
                                                <span className="text-sm font-semibold">
                                                    {tombolTerkunci
                                                        ? "Absen Terkunci"
                                                        : "Absen Sekarang"}
                                                </span>
                                            </button>
                                            <div className="relative w-full sm:w-auto">
                                                <button
                                                    onClick={() =>
                                                        setDropOpen(!dropOpen)
                                                    }
                                                    className="w-full rounded-2xl bg-white px-5 py-3 text-sm font-bold text-teal-700 shadow-lg transition hover:bg-teal-50 sm:w-auto sm:rounded-full"
                                                >
                                                    Lainnya ▾
                                                </button>
                                                {dropOpen && (
                                                    <div className="absolute right-0 z-20 mt-2 w-44 overflow-hidden rounded-xl bg-white text-gray-700 shadow-xl">
                                                        {opsiLainnya.map(
                                                            (o) => (
                                                                <button
                                                                    key={
                                                                        o.value
                                                                    }
                                                                    onClick={() =>
                                                                        pilihStatus(
                                                                            o.value,
                                                                        )
                                                                    }
                                                                    className="block w-full px-4 py-2.5 text-left text-sm hover:bg-teal-50"
                                                                >
                                                                    {o.label}
                                                                </button>
                                                            ),
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        <p className="mt-4 text-center text-[11px] text-emerald-100">
                                            {geofence.aktif ? (
                                                <>
                                                    <b>Masuk</b> hanya di area
                                                    sekolah (≤
                                                    {geofence.radius_meter} m) •{" "}
                                                    <b>Lainnya</b> bisa dari
                                                    mana saja
                                                </>
                                            ) : (
                                                <>
                                                    Klik <b>Masuk</b> = langsung
                                                    tersimpan • <b>Lainnya</b> =
                                                    wajib isi keterangan
                                                </>
                                            )}
                                        </p>
                                    </div>
                                ) : (
                                    <div className="rounded-2xl bg-white p-5 text-center shadow-sm ring-1 ring-slate-900/5">
                                        <p className="text-sm text-gray-500">
                                            Anda sudah absen hari ini
                                        </p>
                                        <div className="mt-2 flex items-center justify-center gap-2">
                                            <span
                                                className={`rounded-full px-4 py-1.5 text-sm font-bold ${statusInfo[absenHariIni.status]?.cls}`}
                                            >
                                                {
                                                    statusInfo[
                                                        absenHariIni.status
                                                    ]?.icon
                                                }{" "}
                                                {
                                                    statusInfo[
                                                        absenHariIni.status
                                                    ]?.label
                                                }
                                            </span>
                                            {absenHariIni.jam_masuk && (
                                                <span className="font-mono text-lg font-bold text-gray-800">
                                                    {absenHariIni.jam_masuk.slice(
                                                        0,
                                                        5,
                                                    )}
                                                </span>
                                            )}
                                        </div>
                                        {absenHariIni.keterangan && (
                                            <p className="mt-2 rounded-lg bg-gray-50 p-2 text-xs italic text-gray-600">
                                                "{absenHariIni.keterangan}"
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>

                            <div className="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-900/5 lg:col-span-3">
                                <h4 className="mb-3 font-bold text-gray-800">
                                    📜 Riwayat Absensi Terakhir
                                </h4>
                                <div className="space-y-2 lg:max-h-[430px] lg:overflow-y-auto lg:pr-1">
                                    {!riwayat || riwayat.length === 0 ? (
                                        <p className="py-8 text-center text-sm text-gray-400">
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
                                                        ).toLocaleDateString(
                                                            "id-ID",
                                                            {
                                                                day: "numeric",
                                                                month: "long",
                                                                year: "numeric",
                                                            },
                                                        )}
                                                    </p>
                                                    {r.keterangan && (
                                                        <p className="text-xs italic text-gray-500">
                                                            "{r.keterangan}"
                                                        </p>
                                                    )}
                                                </div>
                                                <span
                                                    className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-bold ${statusInfo[r.status]?.cls}`}
                                                >
                                                    {statusInfo[r.status]?.icon}{" "}
                                                    {r.jam_masuk
                                                        ? r.jam_masuk.slice(
                                                              0,
                                                              5,
                                                          )
                                                        : statusInfo[r.status]
                                                              ?.label}
                                                </span>
                                            </div>
                                        ))
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="border-t border-slate-100 bg-white p-4 lg:p-6">
                            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h4 className="text-lg font-bold text-gray-800">
                                        📋 Daftar Petugas Hari Ini
                                    </h4>
                                    <p className="text-xs text-gray-500">
                                        {sudahAbsen} sudah absen • {belumAbsen}{" "}
                                        belum absen (alpha)
                                    </p>
                                </div>
                                <div className="flex items-center gap-1 text-xs">
                                    <span className="rounded-full bg-green-100 px-2 py-0.5 font-semibold text-green-700">
                                        ✅ {sudahAbsen}
                                    </span>
                                    <span className="rounded-full bg-red-100 px-2 py-0.5 font-semibold text-red-700">
                                        ❌ {belumAbsen}
                                    </span>
                                </div>
                            </div>

                            <div className="grid gap-2 md:grid-cols-2">
                                {semuaPetugas.length === 0 ? (
                                    <p className="col-span-2 py-8 text-center text-sm text-gray-400">
                                        Tidak ada data petugas.
                                    </p>
                                ) : (
                                    semuaPetugas.map((p) => {
                                        const info =
                                            statusInfo[p.status] ||
                                            statusInfo.alpha;
                                        const punyaAkses = bolehEdit(p);
                                        const isOwn =
                                            p.nama === auth?.user?.name;

                                        return (
                                            <div
                                                key={p.id}
                                                className={`flex items-center justify-between rounded-xl border p-3 transition ${
                                                    p.sudah_absen
                                                        ? "border-gray-200 bg-white"
                                                        : "border-red-200 bg-red-50/50"
                                                }`}
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex items-center gap-2">
                                                        <p className="truncate text-sm font-semibold text-gray-800">
                                                            {p.nama}
                                                            {isOwn && (
                                                                <span className="ml-1 text-[10px] text-indigo-600">
                                                                    (Anda)
                                                                </span>
                                                            )}
                                                        </p>
                                                    </div>
                                                    <p className="truncate text-xs text-gray-500">
                                                        {p.jabatan}
                                                    </p>
                                                    {p.keterangan && (
                                                        <p className="mt-0.5 truncate text-[11px] italic text-gray-500">
                                                            "{p.keterangan}"
                                                        </p>
                                                    )}
                                                    {/* ===== BARU: jarak audit ===== */}
                                                    {p.jarak_meter !== null &&
                                                        p.jarak_meter !==
                                                            undefined && (
                                                            <p className="mt-0.5 text-[10px] text-slate-500">
                                                                📍 Absen dari
                                                                jarak{" "}
                                                                {p.jarak_meter}{" "}
                                                                m
                                                            </p>
                                                        )}
                                                </div>
                                                <div className="flex shrink-0 items-center gap-2">
                                                    <div className="text-right">
                                                        <span
                                                            className={`rounded-full px-2.5 py-1 text-[11px] font-bold ${info.cls}`}
                                                        >
                                                            {info.icon}{" "}
                                                            {info.label}
                                                        </span>
                                                        {p.jam_masuk && (
                                                            <p className="mt-0.5 font-mono text-[11px] text-gray-600">
                                                                {p.jam_masuk.slice(
                                                                    0,
                                                                    5,
                                                                )}
                                                            </p>
                                                        )}
                                                    </div>
                                                    {p.sudah_absen &&
                                                        punyaAkses && (
                                                            <div className="flex gap-1">
                                                                <button
                                                                    onClick={() =>
                                                                        bukaEdit(
                                                                            p,
                                                                        )
                                                                    }
                                                                    className="rounded-lg bg-yellow-100 p-1.5 text-yellow-700 transition hover:bg-yellow-200"
                                                                    title="Edit absensi"
                                                                >
                                                                    ✏️
                                                                </button>
                                                                <button
                                                                    onClick={() =>
                                                                        hapusAbsen(
                                                                            p,
                                                                        )
                                                                    }
                                                                    className="rounded-lg bg-red-100 p-1.5 text-red-700 transition hover:bg-red-200"
                                                                    title="Hapus absensi"
                                                                >
                                                                    🗑️
                                                                </button>
                                                            </div>
                                                        )}
                                                </div>
                                            </div>
                                        );
                                    })
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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

            {editModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
                    <form
                        onSubmit={simpanEdit}
                        className="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"
                    >
                        <h3 className="mb-1 font-bold text-gray-800">
                            ✏️ Edit Absensi
                        </h3>
                        <p className="mb-4 text-sm text-gray-600">
                            {editModal.nama}
                        </p>

                        <div className="space-y-3">
                            <div>
                                <label className="mb-1 block text-xs font-semibold text-gray-700">
                                    Status
                                </label>
                                <select
                                    value={editForm.status}
                                    onChange={(e) =>
                                        setEditForm({
                                            ...editForm,
                                            status: e.target.value,
                                        })
                                    }
                                    className="w-full rounded-lg border-gray-300 text-sm"
                                    required
                                >
                                    {opsiEdit.map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold text-gray-700">
                                    Jam Masuk{" "}
                                    <span className="text-gray-400">
                                        (opsional, format HH:MM)
                                    </span>
                                </label>
                                <input
                                    type="time"
                                    value={editForm.jam_masuk}
                                    onChange={(e) =>
                                        setEditForm({
                                            ...editForm,
                                            jam_masuk: e.target.value,
                                        })
                                    }
                                    className="w-full rounded-lg border-gray-300 text-sm font-mono"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold text-gray-700">
                                    Keterangan{" "}
                                    <span className="text-gray-400">
                                        (opsional)
                                    </span>
                                </label>
                                <textarea
                                    value={editForm.keterangan}
                                    onChange={(e) =>
                                        setEditForm({
                                            ...editForm,
                                            keterangan: e.target.value,
                                        })
                                    }
                                    rows={3}
                                    placeholder="Contoh: Sakit demam, ada surat dokter"
                                    className="w-full rounded-lg border-gray-300 text-sm"
                                />
                            </div>
                        </div>

                        <div className="mt-5 flex gap-2">
                            <button
                                type="button"
                                onClick={() => setEditModal(null)}
                                className="flex-1 rounded-lg bg-gray-200 px-3 py-2 text-sm font-semibold text-gray-700"
                            >
                                Batal
                            </button>
                            <button className="flex-1 rounded-lg bg-teal-600 px-3 py-2 text-sm font-bold text-white hover:bg-teal-700">
                                💾 Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
