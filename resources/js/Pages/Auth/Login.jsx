import { Head, Link, useForm, usePage } from "@inertiajs/react";
import { useEffect, useState } from "react";

export default function Login({ status, canResetPassword }) {
    const pengaturan = usePage().props.pengaturan ?? {
        nama_sekolah: "SMKN 2 Kolaka",
        warna_tema: "#4f46e5",
        logo: null,
    };
    const warna = pengaturan.warna_tema || "#4f46e5";

    // URL logo dari server (aman untuk lokal & Laravel Cloud/S3)
    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);

    const [showPassword, setShowPassword] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        email: "",
        password: "",
        remember: false,
    });

    useEffect(() => {
        return () => reset("password");
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route("login"));
    };

    const fitur = [
        {
            icon: "📱",
            title: "Notifikasi WhatsApp",
            desc: "Orang tua menerima kabar otomatis saat siswa terlambat, izin, atau melanggar.",
        },
        {
            icon: "📊",
            title: "Dashboard Real-time",
            desc: "Statistik harian, grafik tren, donut chart, dan rekap per kelas.",
        },
        {
            icon: "🗂️",
            title: "Data Terpusat",
            desc: "Siswa, wali kelas, wali murid, dan buku tamu dalam satu sistem.",
        },
    ];

    return (
        <div className="flex min-h-screen bg-slate-100">
            <Head title="Masuk" />

            {/* ===== PANEL KIRI: BRANDING ===== */}
            <div
                className="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 text-white lg:flex"
                style={{
                    background: `linear-gradient(160deg, ${warna} 0%, #0f172a 130%)`,
                }}
            >
                {/* Lingkaran dekoratif */}
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div className="absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>

                {/* Header logo */}
                <div className="relative flex items-center gap-3">
                    {logoSrc ? (
                        <img
                            src={logoSrc}
                            alt="Logo"
                            className="h-12 w-12 rounded-xl bg-white object-contain p-1 shadow"
                        />
                    ) : (
                        <span className="flex h-12 w-12 items-center justify-center rounded-xl bg-white/15 text-2xl shadow backdrop-blur">
                            🏫
                        </span>
                    )}
                    <div>
                        <p className="text-sm font-bold leading-tight">
                            {pengaturan.nama_sekolah}
                        </p>
                        <p className="text-xs text-white/60">
                            Sistem Informasi Piket
                        </p>
                    </div>
                </div>

                {/* Konten tengah */}
                <div className="relative">
                    <h1 className="text-4xl font-extrabold leading-tight">
                        Piket Sekolah
                        <br />
                        Digital & Terpadu
                    </h1>
                    <p className="mt-4 max-w-md text-sm leading-relaxed text-white/70">
                        Kelola keterlambatan, izin keluar, pelanggaran, dan buku
                        tamu dalam satu platform — dengan notifikasi WhatsApp
                        otomatis ke orang tua.
                    </p>

                    <div className="mt-8 max-w-md space-y-3">
                        {fitur.map((f) => (
                            <div
                                key={f.title}
                                className="flex items-start gap-3 rounded-xl bg-white/10 p-4 backdrop-blur transition hover:bg-white/15"
                            >
                                <span className="text-2xl">{f.icon}</span>
                                <div>
                                    <p className="text-sm font-semibold">
                                        {f.title}
                                    </p>
                                    <p className="mt-0.5 text-xs leading-relaxed text-white/70">
                                        {f.desc}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                <p className="relative text-xs text-white/50">
                    © {new Date().getFullYear()} {pengaturan.nama_sekolah}.
                    Seluruh hak cipta dilindungi.
                </p>
            </div>

            {/* ===== PANEL KANAN: FORM LOGIN ===== */}
            <div className="flex w-full items-center justify-center p-6 lg:w-1/2">
                <div className="w-full max-w-md">
                    {/* Branding mobile */}
                    <div className="mb-8 flex items-center justify-center gap-3 lg:hidden">
                        {logoSrc ? (
                            <img
                                src={logoSrc}
                                alt="Logo"
                                className="h-12 w-12 rounded-xl bg-white object-contain p-1 shadow"
                            />
                        ) : (
                            <span
                                className="flex h-12 w-12 items-center justify-center rounded-xl text-2xl text-white shadow"
                                style={{ backgroundColor: warna }}
                            >
                                🏫
                            </span>
                        )}
                        <div>
                            <p className="text-sm font-bold leading-tight text-gray-800">
                                {pengaturan.nama_sekolah}
                            </p>
                            <p className="text-xs text-gray-500">
                                Sistem Informasi Piket
                            </p>
                        </div>
                    </div>

                    <div className="rounded-2xl bg-white p-8 shadow-xl">
                        <h2 className="text-2xl font-bold text-gray-800">
                            Selamat Datang 👋
                        </h2>
                        <p className="mt-1 text-sm text-gray-500">
                            Masuk untuk melanjutkan ke dashboard piket.
                        </p>

                        {status && (
                            <div className="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="mt-6 space-y-5">
                            {/* Email */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-gray-700">
                                    Email
                                </label>
                                <div className="relative">
                                    <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <svg
                                            className="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.8}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </span>
                                    <input
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                        placeholder="Namadepan@piket.com"
                                        className="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-3 text-sm shadow-sm focus:border-transparent focus:outline-none focus:ring-2"
                                        style={{ "--tw-ring-color": warna }}
                                        autoFocus
                                    />
                                </div>
                                {errors.email && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.email}
                                    </p>
                                )}
                            </div>

                            {/* Password */}
                            <div>
                                <label className="mb-1.5 block text-sm font-medium text-gray-700">
                                    Kata Sandi
                                </label>
                                <div className="relative">
                                    <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                        <svg
                                            className="h-5 w-5"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={1.8}
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                            />
                                        </svg>
                                    </span>
                                    <input
                                        type={
                                            showPassword ? "text" : "password"
                                        }
                                        value={data.password}
                                        onChange={(e) =>
                                            setData("password", e.target.value)
                                        }
                                        placeholder="••••••••"
                                        className="w-full rounded-lg border border-gray-300 py-2.5 pl-10 pr-10 text-sm shadow-sm focus:border-transparent focus:outline-none focus:ring-2"
                                        style={{ "--tw-ring-color": warna }}
                                    />
                                    <button
                                        type="button"
                                        onClick={() =>
                                            setShowPassword(!showPassword)
                                        }
                                        className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                                        title={
                                            showPassword
                                                ? "Sembunyikan"
                                                : "Lihat"
                                        }
                                    >
                                        {showPassword ? (
                                            <svg
                                                className="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={1.8}
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                                />
                                            </svg>
                                        ) : (
                                            <svg
                                                className="h-5 w-5"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                                strokeWidth={1.8}
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                                />
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                                />
                                            </svg>
                                        )}
                                    </button>
                                </div>
                                {errors.password && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {errors.password}
                                    </p>
                                )}
                            </div>

                            {/* Ingat saya & lupa sandi */}
                            <div className="flex items-center justify-between text-sm">
                                <label className="flex cursor-pointer items-center gap-2 text-gray-600">
                                    <input
                                        type="checkbox"
                                        checked={data.remember}
                                        onChange={(e) =>
                                            setData(
                                                "remember",
                                                e.target.checked,
                                            )
                                        }
                                        className="rounded border-gray-300"
                                        style={{ accentColor: warna }}
                                    />
                                    Ingat saya
                                </label>
                                {canResetPassword && (
                                    <Link
                                        href={route("password.request")}
                                        className="font-medium hover:underline"
                                        style={{ color: warna }}
                                    >
                                        Lupa kata sandi?
                                    </Link>
                                )}
                            </div>

                            {/* Tombol submit */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-90 disabled:opacity-50"
                                style={{ backgroundColor: warna }}
                            >
                                {processing ? (
                                    <>
                                        <svg
                                            className="h-4 w-4 animate-spin"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                        >
                                            <circle
                                                className="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                strokeWidth="4"
                                            ></circle>
                                            <path
                                                className="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
                                            ></path>
                                        </svg>
                                        Memproses...
                                    </>
                                ) : (
                                    <>
                                        Masuk
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
                                                d="M13 7l5 5m0 0l-5 5m5-5H6"
                                            />
                                        </svg>
                                    </>
                                )}
                            </button>
                        </form>

                        <div className="mt-6 border-t border-gray-100 pt-4 text-center text-xs text-gray-500">
                            Belum punya akses? Hubungi administrator sistem
                            untuk mendapatkan akun.
                        </div>
                    </div>

                    <p className="mt-6 text-center text-xs text-gray-400 lg:hidden">
                        © {new Date().getFullYear()} {pengaturan.nama_sekolah}
                        _Designed by Aghoes
                    </p>
                </div>
            </div>
        </div>
    );
}
