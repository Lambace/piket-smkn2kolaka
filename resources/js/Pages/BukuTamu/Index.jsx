import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const today = new Date().toISOString().split("T")[0];

const emptyForm = {
    nama: "",
    telepon: "",
    instansi: "",
    keperluan: "",
    bertemu_dengan: "",
    tanggal_kunjungan: today,
    jam_masuk: "",
    catatan: "",
    foto_ktp: null,
};

export default function Index({ bukuTamu, params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [search, setSearch] = useState(params.search ?? "");
    const [filterTgl, setFilterTgl] = useState(params.tanggal ?? "");

    const list = Array.isArray(bukuTamu?.data) ? bukuTamu.data : [];

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const handleChange = (e) => {
        const { name, value, type, files } = e.target;
        setForm({ ...form, [name]: type === "file" ? files[0] : value });
    };

    const submit = (e) => {
        e.preventDefault();
        const fd = new FormData();
        Object.entries(form).forEach(([k, v]) => {
            if (v !== null && v !== undefined) fd.append(k, v);
        });
        router.post(route("buku-tamu.store"), fd, {
            onSuccess: () => {
                setShowForm(false);
                setForm(emptyForm);
            },
        });
    };

    const catatKeluar = (id) => {
        const jam = new Date().toTimeString().substring(0, 5);
        router.put(route("buku-tamu.update", id), { jam_keluar: jam });
    };

    const remove = (id) => {
        if (confirm("Hapus data tamu ini?"))
            router.delete(route("buku-tamu.destroy", id));
    };

    const onSearch = (v) => {
        setSearch(v);
        router.get(
            route("buku-tamu.index"),
            {
                search: v || undefined,
                tanggal: filterTgl || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const onFilterTgl = (v) => {
        setFilterTgl(v);
        router.get(
            route("buku-tamu.index"),
            {
                search: search || undefined,
                tanggal: v || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Buku Tamu
                </h2>
            }
        >
            <Head title="Buku Tamu" />

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

                {/* Toolbar */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        <input
                            type="text"
                            placeholder="Cari nama / instansi..."
                            value={search}
                            onChange={(e) => onSearch(e.target.value)}
                            className="w-60 rounded-md border-gray-300 shadow-sm"
                        />
                        <input
                            type="date"
                            value={filterTgl}
                            onChange={(e) => onFilterTgl(e.target.value)}
                            className="rounded-md border-gray-300 shadow-sm"
                        />
                    </div>
                    <button
                        onClick={() => setShowForm(!showForm)}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        {showForm ? "Tutup Form" : "+ Tamu Baru"}
                    </button>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            Catat Tamu
                        </h3>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm text-gray-600">
                                    Nama *
                                </label>
                                <input
                                    name="nama"
                                    value={form.nama}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    No. Telepon
                                </label>
                                <input
                                    name="telepon"
                                    value={form.telepon}
                                    onChange={handleChange}
                                    className={inputClass}
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Instansi
                                </label>
                                <input
                                    name="instansi"
                                    value={form.instansi}
                                    onChange={handleChange}
                                    className={inputClass}
                                    placeholder="Mis: Dinas Pendidikan"
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Bertemu dengan
                                </label>
                                <input
                                    name="bertemu_dengan"
                                    value={form.bertemu_dengan}
                                    onChange={handleChange}
                                    className={inputClass}
                                    placeholder="Mis: Kepala Sekolah"
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Tanggal Kunjungan *
                                </label>
                                <input
                                    type="date"
                                    name="tanggal_kunjungan"
                                    value={form.tanggal_kunjungan}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Jam Masuk *
                                </label>
                                <input
                                    type="time"
                                    name="jam_masuk"
                                    value={form.jam_masuk}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Keperluan *
                                </label>
                                <textarea
                                    name="keperluan"
                                    value={form.keperluan}
                                    onChange={handleChange}
                                    rows="2"
                                    className={inputClass}
                                    required
                                ></textarea>
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Catatan
                                </label>
                                <textarea
                                    name="catatan"
                                    value={form.catatan}
                                    onChange={handleChange}
                                    rows="1"
                                    className={inputClass}
                                ></textarea>
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Foto KTP (opsional)
                                </label>
                                <input
                                    type="file"
                                    name="foto_ktp"
                                    accept="image/*"
                                    onChange={handleChange}
                                    className="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-600"
                                />
                            </div>
                        </div>
                        <button className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            Simpan
                        </button>
                    </form>
                )}

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Tanggal</th>
                                <th className="p-3">Nama</th>
                                <th className="p-3">Instansi</th>
                                <th className="p-3">Keperluan</th>
                                <th className="p-3">Masuk</th>
                                <th className="p-3">Keluar</th>
                                <th className="p-3">KTP</th>
                                <th className="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="8"
                                        className="p-4 text-center text-gray-500"
                                    >
                                        Belum ada data tamu.
                                    </td>
                                </tr>
                            ) : (
                                list.map((t) => (
                                    <tr
                                        key={t.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3">
                                            {t.tanggal_kunjungan}
                                        </td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {t.nama}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {t.telepon ?? "-"}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            {t.instansi ?? "-"}
                                        </td>
                                        <td className="p-3 text-xs">
                                            {t.keperluan}
                                        </td>
                                        <td className="p-3">{t.jam_masuk}</td>
                                        <td className="p-3">
                                            {t.jam_keluar ? (
                                                t.jam_keluar
                                            ) : (
                                                <button
                                                    onClick={() =>
                                                        catatKeluar(t.id)
                                                    }
                                                    className="rounded-md bg-blue-500 px-2 py-1 text-xs font-semibold text-white hover:bg-blue-600"
                                                >
                                                    Catat Keluar
                                                </button>
                                            )}
                                        </td>
                                        <td className="p-3">
                                            {t.foto_ktp ? (
                                                <a
                                                    href={t.foto_ktp_url}
                                                    target="_blank"
                                                    className="text-xs font-semibold text-indigo-600 hover:underline"
                                                >
                                                    📷 Lihat
                                                </a>
                                            ) : (
                                                "-"
                                            )}
                                        </td>
                                        <td className="p-3 text-center">
                                            <button
                                                onClick={() => remove(t.id)}
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

                <div className="flex items-center justify-between">
                    <div>
                        {bukuTamu?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(bukuTamu.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {bukuTamu?.current_page ?? 1} dari{" "}
                        {bukuTamu?.last_page ?? 1}
                    </span>
                    <div>
                        {bukuTamu?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(bukuTamu.next_page_url)
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
