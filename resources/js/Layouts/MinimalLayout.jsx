import { Link, usePage } from "@inertiajs/react";

function Icon({ path, className = "h-5 w-5" }) {
    return (
        <svg
            className={className}
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={1.8}
        >
            <path strokeLinecap="round" strokeLinejoin="round" d={path} />
        </svg>
    );
}

export default function MinimalLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const pengaturan = usePage().props.pengaturan ?? {
        nama_sekolah: "SMKN 2 Kolaka",
        warna_tema: "#4f46e5",
        logo: null,
    };
    const logoSrc =
        pengaturan.logo_url ?? (pengaturan.logo ? "/logo.png" : null);

    return (
        <div className="min-h-screen bg-slate-100">
            {/* Header Minimal: hanya logo + nama sekolah + logout */}
            <header className="sticky top-0 z-30 border-b border-slate-200 bg-white shadow-sm">
                <div className="mx-auto flex h-14 max-w-3xl items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-3">
                        {logoSrc ? (
                            <img
                                src={logoSrc}
                                alt="Logo"
                                className="h-8 w-8 rounded-lg bg-white object-contain p-0.5"
                            />
                        ) : (
                            <span className="text-xl">🏫</span>
                        )}
                        <div className="min-w-0">
                            <div className="truncate text-sm font-bold leading-tight text-slate-900">
                                {pengaturan.nama_sekolah}
                            </div>
                            <div className="text-[10px] text-slate-500">
                                Sistem Informasi Piket
                            </div>
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <div className="hidden text-right sm:block">
                            <div className="truncate text-xs font-semibold text-slate-700">
                                {user?.name ?? "User"}
                            </div>
                            <div className="text-[10px] text-slate-500">
                                {user?.role === "koordinator"
                                    ? "Koordinator"
                                    : "Petugas"}
                            </div>
                        </div>
                        <div
                            style={{ backgroundColor: pengaturan.warna_tema }}
                            className="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold text-white"
                        >
                            {user?.name?.charAt(0).toUpperCase() ?? "U"}
                        </div>
                        <Link
                            href={route("logout")}
                            method="post"
                            as="button"
                            title="Keluar"
                            className="rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-red-600"
                        >
                            <Icon
                                path="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                className="h-5 w-5"
                            />
                        </Link>
                    </div>
                </div>
            </header>

            {/* Konten: max-width dibatasi agar rapi di desktop */}
            <main className="mx-auto max-w-3xl p-4 sm:p-6">
                {header && <div className="mb-4">{header}</div>}
                {children}
            </main>

            {/* Footer kecil */}
            <footer className="mx-auto max-w-3xl px-4 pb-6 pt-2 text-center text-[11px] text-slate-400 sm:px-6">
                © {new Date().getFullYear()} {pengaturan.nama_sekolah} — Sistem
                Informasi Piket_Designed by Ags
            </footer>
        </div>
    );
}
