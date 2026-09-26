import { Head, Link, useForm, usePage } from "@inertiajs/react";

export default function ForgotPassword({ status }) {
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

    const { data, setData, post, processing, errors } = useForm({ email: "" });

    const submit = (e) => {
        e.preventDefault();
        post(route("password.email"));
    };

    const langkah = [
        { no: "1", teks: "Masukkan email terdaftar pada formulir di samping." },
        { no: "2", teks: "Periksa kotak masuk email Anda (atau folder spam)." },
        { no: "3", teks: "Klik tautan reset dan buat kata sandi baru." },
    ];

    return (
        <div className="flex min-h-screen bg-slate-100">
            <Head title="Lupa Kata Sandi" />

            {/* ===== PANEL KIRI ===== */}
            <div
                className="relative hidden w-1/2 flex-col justify-between overflow-hidden p-12 text-white lg:flex"
                style={{
                    background: `linear-gradient(160deg, ${warna} 0%, #0f172a 130%)`,
                }}
            >
                <div className="absolute -right-24 -top-24 h-96 w-96 rounded-full bg-white/10 blur-3xl"></div>
                <div className="absolute -left-24 bottom-0 h-80 w-80 rounded-full bg-white/10 blur-3xl"></div>

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

                <div className="relative">
                    <h1 className="text-4xl font-extrabold leading-tight">
                        Kendala Masuk?
                        <br />
                        Kami Bantu Pulihkan.
                    </h1>
                    <p className="mt-4 max-w-md text-sm leading-relaxed text-white/70">
                        Ikuti tiga langkah sederhana berikut untuk mengatur
                        ulang kata sandi akun piket Anda.
                    </p>

                    <div className="mt-8 max-w-md space-y-3">
                        {langkah.map((l) => (
                            <div
                                key={l.no}
                                className="flex items-center gap-4 rounded-xl bg-white/10 p-4 backdrop-blur"
                            >
                                <span
                                    className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white shadow"
                                    style={{ backgroundColor: warna }}
                                >
                                    {l.no}
                                </span>
                                <p className="text-sm text-white/80">
                                    {l.teks}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>

                <p className="relative text-xs text-white/50">
                    © {new Date().getFullYear()} {pengaturan.nama_sekolah}.
                    Seluruh hak cipta dilindungi.
                </p>
            </div>

            {/* ===== PANEL KANAN ===== */}
            <div className="flex w-full items-center justify-center p-6 lg:w-1/2">
                <div className="w-full max-w-md">
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
                        <div
                            className="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl text-2xl text-white shadow-lg"
                            style={{ backgroundColor: warna }}
                        >
                            🔑
                        </div>
                        <h2 className="mt-4 text-center text-2xl font-bold text-gray-800">
                            Lupa Kata Sandi?
                        </h2>
                        <p className="mt-2 text-center text-sm leading-relaxed text-gray-500">
                            Tidak masalah. Masukkan email terdaftar Anda dan
                            kami akan mengirimkan tautan untuk mengatur ulang
                            kata sandi.
                        </p>

                        {status && (
                            <div className="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-700">
                                {status}
                            </div>
                        )}

                        <form onSubmit={submit} className="mt-6 space-y-5">
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
                                        placeholder="nama@sekolah.sch.id"
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
                                        Mengirim Tautan...
                                    </>
                                ) : (
                                    "Kirim Tautan Reset"
                                )}
                            </button>
                        </form>

                        <div className="mt-6 border-t border-gray-100 pt-4 text-center">
                            <Link
                                href={route("login")}
                                className="inline-flex items-center gap-1 text-sm font-medium hover:underline"
                                style={{ color: warna }}
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
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"
                                    />
                                </svg>
                                Kembali ke halaman masuk
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
