import AuthenticatedLayout from "@/layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

const today = new Date().toISOString().split("T")[0];

const emptyForm = {
    siswa_id: "",
    tanggal: today,
    jam_datang: "",
    menit_terlambat: 0,
    keterangan: "",
};

export default function Index({ keterlambatan, daftarSiswa, params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [formKelas, setFormKelas] = useState("");
    const [search, setSearch] = useState(params.search ?? "");
    const [filterTgl, setFilterTgl] = useState(params.tanggal ?? "");

    const list = Array.isArray(keterlambatan?.data) ? keterlambatan.data : [];
    const siswaList = Array.isArray(daftarSiswa) ? daftarSiswa : [];

    // Filter kelas untuk dropdown siswa
    const kelasOptions = [...new Set(siswaList.map((s) => s.kelas))].sort();
    const siswaFiltered = formKelas
        ? siswaList.filter((s) => s.kelas === formKelas)
        : siswaList;

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const hitungMenit = (jam) => {
        if (!jam) return 0;
        const [h, m] = jam.split(":").map(Number);
        const jamMasuk = 7 * 60;
        return Math.max(0, h * 60 + m - jamMasuk);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        const next = {
            ...form,
            [name]: name === "menit_terlambat" ? Number(value) : value,
        };
        if (name === "jam_datang") next.menit_terlambat = hitungMenit(value);
        setForm(next);
    };

    const submit = (e, kirimNotif = false) => {
        e.preventDefault();
        router.post(
            route("keterlambatan.store"),
            { ...form, kirim_notif: kirimNotif },
            {
                onSuccess: () => {
                    setShowForm(false);
                    setForm(emptyForm);
                },
            },
        );
    };

    const siswaTerpilih = siswaList.find((s) => s.id === Number(form.siswa_id));

    const updateStatus = (id, status) => {
        router.put(route("keterlambatan.update", id), { status });
    };

    const remove = (id) => {
        if (confirm("Hapus data keterlambatan ini?")) {
            router.delete(route("keterlambatan.destroy", id));
        }
    };

    const onSearch = (v) => {
        setSearch(v);
        router.get(
            route("keterlambatan.index"),
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
            route("keterlambatan.index"),
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
                    Keterlambatan Siswa
                </h2>
            }
        >
            <Head title="Keterlambatan" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-md bg-green-100 p-3 text-sm text-green-700">
                        {flash.success}
                    </div>
                )}

                {/* Toolbar */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex flex-wrap gap-2">
                        <input
                            type="text"
                            placeholder="Cari nama / NISN..."
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
                        {showForm ? "Tutup Form" : "+ Catat Keterlambatan"}
                    </button>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            Catat Keterlambatan
                        </h3>
                        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label className="text-sm text-gray-600">
                                    Filter Kelas
                                </label>
                                <select
                                    value={formKelas}
                                    onChange={(e) => {
                                        setFormKelas(e.target.value);
                                        setForm({ ...form, siswa_id: "" });
                                    }}
                                    className={inputClass}
                                >
                                    <option value="">-- Semua Kelas --</option>
                                    {kelasOptions.map((k) => (
                                        <option key={k} value={k}>
                                            {k}
                                        </option>
                                    ))}
                                </select>
                                <p className="mt-1 text-xs text-gray-500">
                                    💡 Pilih kelas untuk mempersempit daftar
                                    siswa
                                </p>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Siswa *{" "}
                                    <span className="text-gray-400">
                                        ({siswaFiltered.length} siswa)
                                    </span>
                                </label>
                                <select
                                    name="siswa_id"
                                    value={form.siswa_id}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                >
                                    <option value="">-- Pilih Siswa --</option>
                                    {siswaFiltered.map((s) => (
                                        <option key={s.id} value={s.id}>
                                            {s.nama} — {s.kelas} ({s.nisn})
                                        </option>
                                    ))}
                                </select>
                                {errors.siswa_id && (
                                    <p className="text-xs text-red-600">
                                        {errors.siswa_id}
                                    </p>
                                )}
                                {siswaTerpilih &&
                                    (siswaTerpilih.punya_wa ? (
                                        <p className="mt-1 text-xs text-green-600">
                                            ✓ Nomor WA orang tua tersedia —
                                            notifikasi akan dikirim.
                                        </p>
                                    ) : (
                                        <p className="mt-1 text-xs text-yellow-600">
                                            ⚠ Siswa ini belum punya nomor WA
                                            orang tua — data hanya akan
                                            disimpan.
                                        </p>
                                    ))}
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Tanggal *
                                </label>
                                <input
                                    type="date"
                                    name="tanggal"
                                    value={form.tanggal}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Jam Datang *
                                </label>
                                <input
                                    type="time"
                                    name="jam_datang"
                                    value={form.jam_datang}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                                <p className="mt-1 text-xs text-gray-500">
                                    Otomatis menghitung terlambat dari jam masuk
                                    07:00
                                </p>
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Menit Terlambat
                                </label>
                                <input
                                    type="number"
                                    name="menit_terlambat"
                                    value={form.menit_terlambat}
                                    onChange={handleChange}
                                    min="0"
                                    className={inputClass}
                                />
                            </div>
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Keterangan
                                </label>
                                <textarea
                                    name="keterangan"
                                    value={form.keterangan}
                                    onChange={handleChange}
                                    rows="2"
                                    className={inputClass}
                                ></textarea>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <button
                                type="button"
                                onClick={(e) => submit(e, false)}
                                className="rounded-md bg-gray-600 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700"
                            >
                                Simpan
                            </button>
                            <button
                                type="button"
                                onClick={(e) => submit(e, true)}
                                className="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700"
                            >
                                Simpan & Kirim WA ke Wali
                            </button>
                        </div>
                    </form>
                )}

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Tanggal</th>
                                <th className="p-3">Siswa</th>
                                <th className="p-3">Jam Datang</th>
                                <th className="p-3">Menit</th>
                                <th className="p-3">Status</th>
                                <th className="p-3">Petugas</th>
                                <th className="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            {list.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan="7"
                                        className="p-4 text-center text-gray-500"
                                    >
                                        Belum ada data keterlambatan.
                                    </td>
                                </tr>
                            ) : (
                                list.map((k) => (
                                    <tr
                                        key={k.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3">{k.tanggal}</td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {k.siswa?.nama ?? "-"}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {k.siswa?.kelas ?? ""}
                                            </div>
                                        </td>
                                        <td className="p-3">{k.jam_datang}</td>
                                        <td className="p-3">
                                            <span
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${
                                                    k.menit_terlambat > 30
                                                        ? "bg-red-100 text-red-700"
                                                        : k.menit_terlambat > 10
                                                          ? "bg-yellow-100 text-yellow-700"
                                                          : "bg-green-100 text-green-700"
                                                }`}
                                            >
                                                {k.menit_terlambat} mnt
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <select
                                                value={k.status}
                                                onChange={(e) =>
                                                    updateStatus(
                                                        k.id,
                                                        e.target.value,
                                                    )
                                                }
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${
                                                    k.status === "dimaafkan"
                                                        ? "bg-green-100 text-green-700"
                                                        : k.status === "dihukum"
                                                          ? "bg-red-100 text-red-700"
                                                          : "bg-yellow-100 text-yellow-700"
                                                }`}
                                            >
                                                <option value="dicatat">
                                                    Dicatat
                                                </option>
                                                <option value="dimaafkan">
                                                    Dimaafkan
                                                </option>
                                                <option value="dihukum">
                                                    Dihukum
                                                </option>
                                            </select>
                                        </td>
                                        <td className="p-3 text-xs text-gray-500">
                                            {k.petugas?.name ?? "-"}
                                        </td>
                                        <td className="p-3 text-center">
                                            <button
                                                onClick={() => remove(k.id)}
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
                        {keterlambatan?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(keterlambatan.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {keterlambatan?.current_page ?? 1} dari{" "}
                        {keterlambatan?.last_page ?? 1}
                    </span>
                    <div>
                        {keterlambatan?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(keterlambatan.next_page_url)
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
