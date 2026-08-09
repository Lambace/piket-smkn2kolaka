import AuthenticatedLayout from "@/layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const emptyForm = { nama: "", kelas: "", telepon: "", email: "", aktif: true };

export default function Index({ waliKelas, daftarKelas = [], params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [search, setSearch] = useState(params.search ?? "");

    const list = Array.isArray(waliKelas?.data) ? waliKelas.data : [];
    const kelasOptions = Array.isArray(daftarKelas) ? daftarKelas : [];

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === "checkbox" ? checked : value });
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) {
            router.put(route("wali-kelas.update", editingId), form, {
                onSuccess: () => {
                    setEditingId(null);
                    setShowForm(false);
                    setForm(emptyForm);
                },
            });
        } else {
            router.post(route("wali-kelas.store"), form, {
                onSuccess: () => {
                    setShowForm(false);
                    setForm(emptyForm);
                },
            });
        }
    };

    const startEdit = (w) => {
        setEditingId(w.id);
        setForm({
            nama: w.nama,
            kelas: w.kelas,
            telepon: w.telepon ?? "",
            email: w.email ?? "",
            aktif: !!w.aktif,
        });
        setShowForm(true);
    };

    const remove = (id) => {
        if (confirm("Hapus data wali kelas ini?"))
            router.delete(route("wali-kelas.destroy", id));
    };

    const onSearch = (v) => {
        setSearch(v);
        router.get(
            route("wali-kelas.index"),
            { search: v || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    // ===== KIRIM REKAP HARIAN KE SEMUA WALI KELAS =====
    const kirimRekap = () => {
        if (confirm("Kirim rekap harian sekarang ke semua wali kelas aktif?")) {
            router.post(route("rekap.kirim"));
        }
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Wali Kelas
                </h2>
            }
        >
            <Head title="Wali Kelas" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-md bg-green-100 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                {/* Toolbar */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <input
                        type="text"
                        placeholder="Cari nama / kelas / telepon..."
                        value={search}
                        onChange={(e) => onSearch(e.target.value)}
                        className="w-64 rounded-md border-gray-300 shadow-sm"
                    />
                    <div className="flex flex-wrap gap-2">
                        <button
                            onClick={kirimRekap}
                            className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                        >
                            📨 Kirim Rekap Sekarang
                        </button>
                        <button
                            onClick={() => {
                                setShowForm(!showForm);
                                setEditingId(null);
                                setForm(emptyForm);
                            }}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                        >
                            {showForm ? "Tutup Form" : "+ Tambah Wali Kelas"}
                        </button>
                    </div>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            {editingId
                                ? "Edit Wali Kelas"
                                : "Tambah Wali Kelas"}
                        </h3>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm text-gray-600">
                                    Nama Guru *
                                </label>
                                <input
                                    name="nama"
                                    value={form.nama}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                                {errors.nama && (
                                    <p className="text-xs text-red-600">
                                        {errors.nama}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Kelas *
                                </label>
                                <select
                                    name="kelas"
                                    value={form.kelas}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                >
                                    <option value="">-- Pilih Kelas --</option>
                                    {kelasOptions.map((k) => (
                                        <option key={k} value={k}>
                                            {k}
                                        </option>
                                    ))}
                                </select>
                                {errors.kelas && (
                                    <p className="text-xs text-red-600">
                                        {errors.kelas}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    💡 Daftar kelas diambil otomatis dari Data
                                    Siswa
                                </p>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    No. WhatsApp
                                </label>
                                <input
                                    name="telepon"
                                    value={form.telepon}
                                    onChange={handleChange}
                                    placeholder="081234567890"
                                    className={inputClass}
                                />
                                {errors.telepon && (
                                    <p className="text-xs text-red-600">
                                        {errors.telepon}
                                    </p>
                                )}
                                <p className="mt-1 text-xs text-gray-500">
                                    📱 Untuk menerima rekap harian otomatis
                                </p>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Email
                                </label>
                                <input
                                    name="email"
                                    type="email"
                                    value={form.email}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                                {errors.email && (
                                    <p className="text-xs text-red-600">
                                        {errors.email}
                                    </p>
                                )}
                            </div>
                            <div className="md:col-span-2">
                                <label className="flex items-center gap-2 text-sm text-gray-600">
                                    <input
                                        type="checkbox"
                                        name="aktif"
                                        checked={form.aktif}
                                        onChange={handleChange}
                                        className="rounded border-gray-300"
                                    />
                                    Aktif (menerima rekap harian)
                                </label>
                            </div>
                        </div>
                        <button className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            {editingId ? "Update Data" : "Simpan Data"}
                        </button>
                    </form>
                )}

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Nama Guru</th>
                                <th className="p-3">Kelas</th>
                                <th className="p-3">No. WhatsApp</th>
                                <th className="p-3">Email</th>
                                <th className="p-3">Status</th>
                                <th className="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="6"
                                        className="p-4 text-center text-gray-500"
                                    >
                                        Belum ada data wali kelas.
                                    </td>
                                </tr>
                            ) : (
                                list.map((w) => (
                                    <tr
                                        key={w.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3 font-medium">
                                            {w.nama}
                                        </td>
                                        <td className="p-3">
                                            <span className="rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">
                                                {w.kelas}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            {w.telepon ?? "-"}
                                        </td>
                                        <td className="p-3">
                                            {w.email ?? "-"}
                                        </td>
                                        <td className="p-3">
                                            {w.aktif ? (
                                                <span className="rounded-md bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">
                                                    Aktif
                                                </span>
                                            ) : (
                                                <span className="rounded-md bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-500">
                                                    Nonaktif
                                                </span>
                                            )}
                                        </td>
                                        <td className="p-3 text-center">
                                            <button
                                                onClick={() => startEdit(w)}
                                                className="mr-2 rounded bg-yellow-500 px-2 py-1 text-xs font-semibold text-white hover:bg-yellow-600"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                onClick={() => remove(w.id)}
                                                className="rounded bg-red-500 px-2 py-1 text-xs font-semibold text-white hover:bg-red-600"
                                            >
                                                Hapus
                                            </button>
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
                        {waliKelas?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(waliKelas.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {waliKelas?.current_page ?? 1} dari{" "}
                        {waliKelas?.last_page ?? 1}
                    </span>
                    <div>
                        {waliKelas?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(waliKelas.next_page_url)
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
