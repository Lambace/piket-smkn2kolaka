import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";
import { useRef } from "react";

const emptyForm = { nama: "", kelas: "", telepon: "", email: "", aktif: true };

export default function Index({ waliKelas, daftarKelas = [], params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);
    const [form, setForm] = useState(emptyForm);
    const [search, setSearch] = useState(params.search ?? "");
    const formRef = useRef(null);
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

    const scrollToForm = (w) => {
        // Isi form dengan data wali kelas
        setEditingId(w.id);
        setForm({
            nama: w.nama,
            kelas: w.kelas,
            telepon: w.telepon ?? "",
            email: w.email ?? "",
            aktif: !!w.aktif,
        });

        // Tampilkan form jika tersembunyi
        if (!showForm) {
            setShowForm(true);
        }

        // Scroll ke form dengan smooth animation
        setTimeout(() => {
            if (formRef.current) {
                formRef.current.scrollIntoView({
                    behavior: "smooth",
                    block: "start",
                });
            }
        }, 100);
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

    const kirimRekap = () => {
        if (confirm("Kirim rekap harian sekarang ke semua wali kelas aktif?")) {
            router.post(route("rekap.kirim"));
        }
    };

    // ===== STATUS BADGE =====
    const StatusBadge = ({ aktif }) =>
        aktif ? (
            <span className="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                <span className="mr-1 h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Aktif
            </span>
        ) : (
            <span className="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-500">
                <span className="mr-1 h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                Nonaktif
            </span>
        );

    // ===== CARD MOBILE (tampilan di layar kecil) =====
    const WaliCard = ({ w }) => (
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div className="mb-3 flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                    <button
                        onClick={() => scrollToForm(w)}
                        className="w-full text-left text-base font-semibold text-indigo-600 hover:text-indigo-800 hover:underline focus:outline-none"
                    >
                        {w.nama}
                    </button>
                    <div className="mt-1 flex flex-wrap items-center gap-1.5">
                        <span className="inline-flex items-center rounded-md bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                            {w.kelas}
                        </span>
                        <StatusBadge aktif={w.aktif} />
                    </div>
                </div>
            </div>

            <div className="space-y-1.5 text-sm text-gray-600">
                <div className="flex items-center gap-2">
                    <span className="text-xs text-gray-400"></span>
                    <span className="truncate">{w.telepon ?? "—"}</span>
                </div>
                {w.email && (
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-gray-400">✉️</span>
                        <span className="truncate">{w.email}</span>
                    </div>
                )}
            </div>

            {/* Tombol horizontal di mobile */}
            <div className="mt-3 flex items-center gap-2 border-t border-gray-100 pt-3">
                <button
                    onClick={() => startEdit(w)}
                    className="flex-1 rounded-md bg-yellow-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-yellow-600"
                >
                    ✏️ Edit
                </button>
                <button
                    onClick={() => remove(w.id)}
                    className="flex-1 rounded-md bg-red-500 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-600"
                >
                    🗑️ Hapus
                </button>
            </div>
        </div>
    );

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
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <input
                        type="text"
                        placeholder="Cari nama / kelas / telepon..."
                        value={search}
                        onChange={(e) => onSearch(e.target.value)}
                        className="w-full rounded-md border-gray-300 shadow-sm sm:w-64"
                    />
                    <div className="flex flex-wrap gap-2">
                        <button
                            onClick={kirimRekap}
                            className="flex-1 rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700 sm:flex-none"
                        >
                            Kirim Rekap
                        </button>
                        <button
                            onClick={() => {
                                setShowForm(!showForm);
                                setEditingId(null);
                                setForm(emptyForm);
                            }}
                            className="flex-1 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 sm:flex-none"
                        >
                            {showForm ? " Tutup Form" : "+ Tambah"}
                        </button>
                    </div>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        ref={formRef}
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
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Email{" "}
                                    <span className="text-xs text-gray-400">
                                        (opsional)
                                    </span>
                                </label>
                                <input
                                    name="email"
                                    type="email"
                                    value={form.email}
                                    onChange={handleChange}
                                    placeholder="boleh kosong"
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

                {/* ===== TABEL DESKTOP (hidden di mobile) ===== */}
                <div className="hidden overflow-x-auto rounded-lg bg-white shadow md:block">
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
                                        <td className="p-3">
                                            <button
                                                onClick={() => scrollToForm(w)}
                                                className="text-left font-medium text-indigo-600 hover:text-indigo-800 hover:underline focus:outline-none"
                                            >
                                                {w.nama}
                                            </button>
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
                                            <StatusBadge aktif={w.aktif} />
                                        </td>
                                        <td className="p-3">
                                            <div className="flex items-center justify-center gap-2">
                                                <button
                                                    onClick={() => startEdit(w)}
                                                    className="rounded bg-yellow-500 px-3 py-1 text-xs font-semibold text-white hover:bg-yellow-600"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    onClick={() => remove(w.id)}
                                                    className="rounded bg-red-500 px-3 py-1 text-xs font-semibold text-white hover:bg-red-600"
                                                >
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* ===== CARD MOBILE (hanya tampil di layar kecil) ===== */}
                <div className="space-y-3 md:hidden">
                    {list.length === 0 ? (
                        <div className="rounded-lg bg-white p-6 text-center text-gray-500 shadow">
                            Belum ada data wali kelas.
                        </div>
                    ) : (
                        list.map((w) => <WaliCard key={w.id} w={w} />)
                    )}
                </div>

                {/* Pagination */}
                <div className="flex flex-col items-center justify-between gap-3 sm:flex-row">
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
