import AuthenticatedLayout from "@/layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function Index({ notifikasi, params = {} }) {
    const { flash } = usePage().props;
    const [filterStatus, setFilterStatus] = useState(params.status ?? "");

    const list = Array.isArray(notifikasi?.data) ? notifikasi.data : [];

    const onFilter = (v) => {
        setFilterStatus(v);
        router.get(
            route("notifikasi.index"),
            { status: v || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    const retry = (id) => {
        router.post(route("notifikasi.retry", id));
    };

    const statusBadge = (status) => {
        if (status === "terkirim") return "bg-green-100 text-green-700";
        if (status === "gagal") return "bg-red-100 text-red-700";
        return "bg-yellow-100 text-yellow-700";
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Notifikasi WhatsApp
                </h2>
            }
        >
            <Head title="Notifikasi WA" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-md bg-green-100 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-md bg-red-100 p-3 text-sm text-red-700">
                        {flash.error}
                    </div>
                )}

                {/* Filter */}
                <div className="flex items-center gap-3">
                    <span className="text-sm font-semibold text-gray-600">
                        Filter Status:
                    </span>
                    <select
                        value={filterStatus}
                        onChange={(e) => onFilter(e.target.value)}
                        className="rounded-md border-gray-300 text-sm shadow-sm"
                    >
                        <option value="">Semua</option>
                        <option value="terkirim">Terkirim</option>
                        <option value="gagal">Gagal</option>
                        <option value="menunggu">Menunggu</option>
                    </select>
                </div>

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Waktu</th>
                                <th className="p-3">Nomor Tujuan</th>
                                <th className="p-3">Pesan</th>
                                <th className="p-3">Status</th>
                                <th className="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="5"
                                        className="p-4 text-center text-gray-500"
                                    >
                                        Belum ada log notifikasi. Catat
                                        keterlambatan/izin/pelanggaran terlebih
                                        dahulu.
                                    </td>
                                </tr>
                            ) : (
                                list.map((n) => (
                                    <tr
                                        key={n.id}
                                        className="border-t border-gray-200 align-top"
                                    >
                                        <td className="p-3 whitespace-nowrap text-xs text-gray-500">
                                            {n.created_at
                                                ?.replace("T", " ")
                                                .substring(0, 16)}
                                        </td>
                                        <td className="p-3 whitespace-nowrap">
                                            {n.nomor_tujuan}
                                        </td>
                                        <td className="p-3">
                                            <details>
                                                <summary className="cursor-pointer text-xs text-indigo-600">
                                                    Lihat pesan
                                                </summary>
                                                <pre className="mt-2 whitespace-pre-wrap rounded bg-gray-50 p-2 text-xs text-gray-700">
                                                    {n.pesan}
                                                </pre>
                                            </details>
                                            {n.pesan_error && (
                                                <p className="mt-1 text-xs text-red-600">
                                                    ⚠ {n.pesan_error}
                                                </p>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            <span
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${statusBadge(n.status)}`}
                                            >
                                                {n.status}
                                            </span>
                                        </td>
                                        <td className="p-3 text-center">
                                            {n.status !== "terkirim" && (
                                                <button
                                                    onClick={() => retry(n.id)}
                                                    className="rounded bg-blue-500 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-600"
                                                >
                                                    ↻ Kirim Ulang
                                                </button>
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination */}
                <div className="flex items-center justify-between">
                    <div>
                        {notifikasi?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(notifikasi.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {notifikasi?.current_page ?? 1} dari{" "}
                        {notifikasi?.last_page ?? 1}
                    </span>
                    <div>
                        {notifikasi?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(notifikasi.next_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                Berikutnya →
                            </button>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
