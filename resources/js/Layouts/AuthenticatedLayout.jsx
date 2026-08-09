import { Link, usePage } from "@inertiajs/react";
import { useState } from "react";

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

const icons = {
    dashboard:
        "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
    siswa: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
    waliKelas:
        "M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222",
    waliMurid:
        "M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z",
    terlambat: "M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z",
    izin: "M17 8l4 4m0 0l-4 4m4-4H3",
    tamu: "M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253",
    pelanggaran:
        "M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z",
    notifikasi:
        "M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9",
    logout: "M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1",
    pengaturan:
        "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-2.572-1.065c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z",
};

export default function AuthenticatedLayout({ header, children }) {
    const user = usePage().props.auth.user;
    const pengaturan = usePage().props.pengaturan ?? {
        nama_sekolah: "SMKN 2 Kolaka",
        warna_tema: "#4f46e5",
        logo: null,
    };

    // URL logo dari server (aman untuk lokal & Laravel Cloud/S3)
    const logoSrc =
        pengaturan.logo_url ??
        (pengaturan.logo ? `/storage/${pengaturan.logo}` : null);

    const [sidebarOpen, setSidebarOpen] = useState(false);

    const menuGroups = [
        {
            title: "Menu Utama",
            items: [
                {
                    name: "Dashboard",
                    href: route("dashboard"),
                    active: route().current("dashboard"),
                    icon: icons.dashboard,
                },
            ],
        },
        {
            title: "Data Master",
            items: [
                {
                    name: "Data Siswa",
                    href: route("siswa.index"),
                    active: route().current("siswa.*"),
                    icon: icons.siswa,
                },
                {
                    name: "Wali Kelas",
                    href: route("wali-kelas.index"),
                    active: route().current("wali-kelas.*"),
                    icon: icons.waliKelas,
                },
                {
                    name: "Wali Murid",
                    href: route("wali-murid.index"),
                    active: route().current("wali-murid.*"),
                    icon: icons.waliMurid,
                },
            ],
        },
        {
            title: "Piket Harian",
            items: [
                {
                    name: "Keterlambatan",
                    href: route("keterlambatan.index"),
                    active: route().current("keterlambatan.*"),
                    icon: icons.terlambat,
                },
                {
                    name: "Izin Keluar",
                    href: route("izin-keluar.index"),
                    active: route().current("izin-keluar.*"),
                    icon: icons.izin,
                },
                {
                    name: "Buku Tamu",
                    href: route("buku-tamu.index"),
                    active: route().current("buku-tamu.*"),
                    icon: icons.tamu,
                },
                {
                    name: "Pelanggaran",
                    href: route("pelanggaran.index"),
                    active: route().current("pelanggaran.*"),
                    icon: icons.pelanggaran,
                },
            ],
        },
        {
            title: "Sistem",
            items: [
                {
                    name: "Laporan",
                    href: route("laporan.index"),
                    active: route().current("laporan.*"),
                    icon: "M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
                },

                {
                    name: "Notifikasi WA",
                    href: route("notifikasi.index"),
                    active: route().current("notifikasi.*"),
                    icon: icons.notifikasi,
                },
                {
                    name: "Pengaturan",
                    href: route("pengaturan.edit"),
                    active: route().current("pengaturan.*"),
                    icon: icons.pengaturan,
                },
            ],
        },
    ];

    const sidebarContent = (
        <div className="flex h-full flex-col">
            {/* Logo & Nama Sekolah Dinamis */}
            <div className="flex h-16 shrink-0 items-center gap-3 border-b border-slate-800 px-6">
                {logoSrc ? (
                    <img
                        src={logoSrc}
                        alt="Logo"
                        className="h-9 w-9 rounded-lg bg-white object-contain p-0.5"
                    />
                ) : (
                    <span className="text-2xl">🏫</span>
                )}
                <div className="min-w-0">
                    <div className="truncate text-sm font-bold leading-tight text-white">
                        {pengaturan.nama_sekolah}
                    </div>
                    <div className="text-xs text-slate-400">
                        Sistem Informasi Piket
                    </div>
                </div>
            </div>

            {/* Menu */}
            <nav className="sidebar-scroll flex-1 space-y-7 overflow-y-auto px-4 py-6">
                {menuGroups.map((group) => (
                    <div key={group.title}>
                        <div className="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                            {group.title}
                        </div>
                        <div className="mt-2 space-y-1">
                            {group.items.map((item) => (
                                <Link
                                    key={item.name}
                                    href={item.href}
                                    onClick={() => setSidebarOpen(false)}
                                    style={
                                        item.active
                                            ? {
                                                  backgroundColor:
                                                      pengaturan.warna_tema,
                                              }
                                            : undefined
                                    }
                                    className={`flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                                        item.active
                                            ? "text-white shadow"
                                            : "text-slate-300 hover:bg-slate-800 hover:text-white"
                                    }`}
                                >
                                    <Icon
                                        path={item.icon}
                                        className="h-5 w-5 shrink-0"
                                    />
                                    {item.name}
                                </Link>
                            ))}
                        </div>
                    </div>
                ))}
            </nav>

            {/* Profil User */}
            <div className="shrink-0 border-t border-slate-800 p-4">
                <div className="flex items-center gap-3">
                    <div
                        style={{ backgroundColor: pengaturan.warna_tema }}
                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                    >
                        {user.name.charAt(0).toUpperCase()}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="truncate text-sm font-semibold text-white">
                            {user.name}
                        </div>
                        <div className="truncate text-xs text-slate-400">
                            {user.email}
                        </div>
                    </div>
                    <Link
                        href={route("logout")}
                        method="post"
                        as="button"
                        title="Keluar"
                        className="text-slate-400 transition hover:text-red-400"
                    >
                        <Icon path={icons.logout} className="h-5 w-5" />
                    </Link>
                </div>
            </div>
        </div>
    );

    return (
        <div className="min-h-screen bg-slate-100">
            {/* Topbar Mobile */}
            <div className="sticky top-0 z-40 flex h-14 items-center justify-between bg-slate-900 px-4 lg:hidden">
                <button
                    onClick={() => setSidebarOpen(true)}
                    className="rounded-md p-2 text-slate-300 hover:bg-slate-800 hover:text-white"
                >
                    <svg
                        className="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M4 6h16M4 12h16M4 18h16"
                        />
                    </svg>
                </button>
                <span className="truncate px-2 text-sm font-bold text-white">
                    {pengaturan.nama_sekolah}
                </span>
                <Link
                    href={route("logout")}
                    method="post"
                    as="button"
                    className="rounded-md p-2 text-slate-300 hover:bg-slate-800 hover:text-red-400"
                >
                    <Icon path={icons.logout} className="h-5 w-5" />
                </Link>
            </div>

            {/* Drawer Sidebar Mobile */}
            {sidebarOpen && (
                <div className="fixed inset-0 z-50 lg:hidden">
                    <div
                        className="fixed inset-0 bg-black/60"
                        onClick={() => setSidebarOpen(false)}
                    ></div>
                    <div className="fixed inset-y-0 left-0 flex w-72 max-w-[85%] flex-col bg-slate-900 shadow-2xl">
                        <button
                            onClick={() => setSidebarOpen(false)}
                            className="absolute right-3 top-4 rounded-md p-1 text-slate-400 hover:text-white"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                        {sidebarContent}
                    </div>
                </div>
            )}

            {/* Sidebar Desktop */}
            <aside className="fixed inset-y-0 left-0 z-30 hidden w-72 bg-slate-900 lg:block">
                {sidebarContent}
            </aside>

            {/* Konten */}
            <div className="lg:pl-72">
                {header && (
                    <header className="border-b border-slate-200 bg-white shadow-sm">
                        <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                            {header}
                        </div>
                    </header>
                )}
                <main className="p-4 sm:p-6 lg:p-8">{children}</main>
            </div>
        </div>
    );
}
