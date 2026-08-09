import AuthenticatedLayout from "@/layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const emptyForm = {
    siswa_id: "",
    nama: "",
    hubungan: "Ayah",
    telepon: "",
    utama: false,
};

export default function Index({ waliMurid, daftarSiswa, params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [search, setSearch] = useState(params.search ?? "");

    const list = Array.isArray(waliMurid?.data) ? waliMurid.data : [];
    const siswaList = Array.isArray(daftarSiswa) ? daftarSiswa : [];

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm({ ...form, [name]: type === "checkbox" ? checked : value });
    };

    const submit = (e) => {
        e.preventDefault();
        if (editingId) {
            router.put(route("wali-murid.update", editingId), form, {
                onSuccess: () => {
                    setEditingId(null);
                    setShowForm(false);
                    setForm(emptyForm);
                },
            });
        } else {
            router.post(route("wali-murid.store"), form, {
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
            siswa_id: w.siswa_id,
            nama: w.nama,
            hubungan: w.hubungan,
            telepon: w.telepon,
            utama: !!w.utama,
        });
        setShowForm(true);
    };

    const remove = (id) => {
        if (confirm("Hapus data wali ini?"))
            router.delete(route("wali-murid.destroy", id));
    };

    const onSearch = (v) => {
        setSearch(v);
        router.get(
            route("wali-murid.index"),
            { search: v || undefined },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Wali Murid
                </h2>
            }
        >
            <Head title="Wali Murid" />

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
                        placeholder="Cari nama wali / telepon / siswa..."
                        value={search}
                        onChange={(e) => onSearch(e.target.value)}
                        className="w-64 rounded-md border-gray-300 shadow-sm"
                    />
                    <button
                        onClick={() => {
                            setShowForm(!showForm);
                            setEditingId(null);
                            setForm(emptyForm);
                        }}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        {showForm ? "Tutup Form" : "+ Tambah Wali"}
                    </button>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            {editingId ? "Edit Wali" : "Tambah Wali"}
                        </h3>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm text-gray-600">
                                    Siswa *
                                </label>
                                <select
                                    name="siswa_id"
                                    value={form.siswa_id}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                >
                                    <option value="">-- Pilih Siswa --</option>
                                    {siswaList.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.nama} — {s.kelas}
                                        </option>
                                    ))}
                                </select>
                                {errors.siswa_id && (
                                    <p className="text-xs text-red-600">
                                        {errors.siswa_id}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Nama Wali *
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
                                    Hubungan *
                                </label>
                                <select
                                    name="hubungan"
                                    value={form.hubungan}
                                    onChange={handleChange}
                                    className={inputClass}
                                >
                                    <option value="Ayah">Ayah</option>
                                    <option value="Ibu">Ibu</option>
                                    <option value="Wali">Wali Lainnya</option>
                                </select>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    No. WhatsApp *
                                </label>
                                <input
                                    name="telepon"
                                    value={form.telepon}
                                    onChange={handleChange}
                                    placeholder="081234567890"
                                    className={inputClass}
                                    required
                                />
                                {errors.telepon && (
                                    <p className="text-xs text-red-600">
                                        {errors.telepon}
                                    </p>
                                )}
                            </div>
                            <div className="md:col-span-2">
                                <label className="flex items-center gap-2 text-sm text-gray-600">
                                    <input
                                        type="checkbox"
                                        name="utama"
                                        checked={form.utama}
                                        onChange={handleChange}
                                        className="rounded border-gray-300"
                                    />
                                    Jadikan wali utama (penerima notifikasi WA)
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
                                <th className="p-3">Nama Wali</th>
                                <th className="p-3">Hubungan</th>
                                <th className="p-3">No. WhatsApp</th>
                                <th className="p-3">Siswa</th>
                                <th className="p-3">Utama</th>
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
                                        Belum ada data wali murid.
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
                                        <td className="p-3">{w.hubungan}</td>
                                        <td className="p-3">{w.telepon}</td>
                                        <td className="p-3">
                                            <div>{w.siswa?.nama ?? "-"}</div>
                                            <div className="text-xs text-gray-500">
                                                {w.siswa?.kelas ?? ""}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            {w.utama ? (
                                                <span className="rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">
                                                    ⭐ Utama
                                                </span>
                                            ) : (
                                                "-"
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
                        {waliMurid?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(waliMurid.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {waliMurid?.current_page ?? 1} dari{" "}
                        {waliMurid?.last_page ?? 1}
                    </span>
                    <div>
                        {waliMurid?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(waliMurid.next_page_url)
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
