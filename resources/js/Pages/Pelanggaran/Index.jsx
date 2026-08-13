import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";

const today = new Date().toISOString().split("T")[0];

const emptyForm = {
    siswa_id: "",
    tanggal: today,
    jenis_pelanggaran: "",
    poin: 5,
    keterangan: "",
    foto_bukti: null,
};

// ===== Fungsi format tanggal Indonesia =====
const formatTanggal = (tgl) => {
    if (!tgl) return "-";
    const d = new Date(tgl);
    const hari = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
    ];
    const bulan = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "Mei",
        "Jun",
        "Jul",
        "Agu",
        "Sep",
        "Okt",
        "Nov",
        "Des",
    ];
    return `${hari[d.getDay()]}, ${d.getDate()} ${bulan[d.getMonth()]} ${d.getFullYear()}`;
};

export default function Index({ pelanggaran, daftarSiswa, params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [formKelas, setFormKelas] = useState("");
    const [filterTgl, setFilterTgl] = useState(params.tanggal ?? "");
    const [searchTerm, setSearchTerm] = useState(params.search ?? "");

    const list = Array.isArray(pelanggaran?.data) ? pelanggaran.data : [];
    const siswaList = Array.isArray(daftarSiswa) ? daftarSiswa : [];

    // Filter kelas untuk dropdown siswa
    const kelasOptions = [...new Set(siswaList.map((s) => s.kelas))].sort();
    const siswaFiltered = formKelas
        ? siswaList.filter((s) => s.kelas === formKelas)
        : siswaList;

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const handleChange = (e) => {
        const { name, value, type, files } = e.target;
        setForm({ ...form, [name]: type === "file" ? files[0] : value });
    };

    const submit = (e, kirimNotif = false) => {
        e.preventDefault();
        const fd = new FormData();
        Object.entries(form).forEach(([k, v]) => {
            if (v !== null && v !== undefined) fd.append(k, v);
        });
        fd.append("kirim_notif", kirimNotif ? "1" : "0");

        router.post(route("pelanggaran.store"), fd, {
            onSuccess: () => {
                setShowForm(false);
                setForm(emptyForm);
            },
        });
    };

    const siswaTerpilih = siswaList.find((s) => s.id === Number(form.siswa_id));

    const updateStatus = (id, status) => {
        router.put(route("pelanggaran.update", id), { status });
    };

    const remove = (id) => {
        if (confirm("Hapus data pelanggaran ini?")) {
            router.delete(route("pelanggaran.destroy", id));
        }
    };

    // ===== LIVE SEARCH dengan debounce 400ms =====
    useEffect(() => {
        const timer = setTimeout(() => {
            if (
                searchTerm !== (params.search ?? "") ||
                filterTgl !== (params.tanggal ?? "")
            ) {
                router.get(
                    route("pelanggaran.index"),
                    {
                        search: searchTerm || undefined,
                        tanggal: filterTgl || undefined,
                    },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    },
                );
            }
        }, 400);
        return () => clearTimeout(timer);
    }, [searchTerm, filterTgl]);

    const statusBadge = (status) => {
        switch (status) {
            case "selesai":
                return "bg-green-100 text-green-700";
            case "diproses":
                return "bg-yellow-100 text-yellow-700";
            default:
                return "bg-red-100 text-red-700";
        }
    };

    const poinColor = (poin) => {
        if (poin >= 30) return "bg-red-100 text-red-700";
        if (poin >= 10) return "bg-yellow-100 text-yellow-700";
        return "bg-blue-100 text-blue-700";
    };

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Pelanggaran Siswa
                </h2>
            }
        >
            <Head title="Pelanggaran" />

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
                        <div className="relative">
                            <input
                                type="text"
                                placeholder="🔍 Cari nama / NISN / kelas..."
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                                className="w-64 rounded-md border-gray-300 shadow-sm pl-9"
                            />
                            <svg
                                className="absolute left-3 top-2.5 h-4 w-4 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth={2}
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                        <input
                            type="date"
                            value={filterTgl}
                            onChange={(e) => setFilterTgl(e.target.value)}
                            className="rounded-md border-gray-300 shadow-sm"
                            title="Filter berdasarkan tanggal"
                        />
                        {(searchTerm || filterTgl) && (
                            <button
                                onClick={() => {
                                    setSearchTerm("");
                                    setFilterTgl("");
                                    router.get(
                                        route("pelanggaran.index"),
                                        {},
                                        { preserveState: false },
                                    );
                                }}
                                className="rounded-md bg-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-300"
                                title="Hapus semua filter"
                            >
                                ✕ Reset
                            </button>
                        )}
                    </div>
                    <button
                        onClick={() => setShowForm(!showForm)}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                    >
                        {showForm ? "Tutup Form" : "+ Catat Pelanggaran"}
                    </button>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            Catat Pelanggaran
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
                                            ✓ Nomor WA orang tua tersedia.
                                        </p>
                                    ) : (
                                        <p className="mt-1 text-xs text-yellow-600">
                                            ⚠ Siswa ini belum punya nomor WA
                                            orang tua.
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
                                    Jenis Pelanggaran *
                                </label>
                                <input
                                    name="jenis_pelanggaran"
                                    value={form.jenis_pelanggaran}
                                    onChange={handleChange}
                                    placeholder="Mis: Merokok"
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Poin *
                                </label>
                                <input
                                    type="number"
                                    name="poin"
                                    value={form.poin}
                                    onChange={handleChange}
                                    min="0"
                                    max="100"
                                    className={inputClass}
                                    required
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
                            <div className="md:col-span-2">
                                <label className="text-sm text-gray-600">
                                    Foto Bukti (opsional)
                                </label>
                                <input
                                    type="file"
                                    name="foto_bukti"
                                    accept="image/*"
                                    onChange={handleChange}
                                    className="block w-full text-sm text-gray-600 file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-600"
                                />
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

                {/* Info filter aktif */}
                {(searchTerm || filterTgl) && (
                    <div className="rounded-md bg-blue-50 p-2 text-xs text-blue-700">
                        🔍 Filter aktif:{" "}
                        {searchTerm && (
                            <span className="font-semibold">
                                kata kunci "{searchTerm}"
                            </span>
                        )}
                        {searchTerm && filterTgl && " • "}
                        {filterTgl && (
                            <span className="font-semibold">
                                tanggal {formatTanggal(filterTgl)}
                            </span>
                        )}{" "}
                        → {list.length} data ditemukan
                    </div>
                )}

                {/* Tabel */}
                <div className="overflow-x-auto rounded-lg bg-white shadow">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-left text-gray-600">
                            <tr>
                                <th className="p-3">Tanggal</th>
                                <th className="p-3">Siswa</th>
                                <th className="p-3">Jenis</th>
                                <th className="p-3">Poin</th>
                                <th className="p-3">Status</th>
                                <th className="p-3">Bukti</th>
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
                                        {searchTerm || filterTgl
                                            ? "Tidak ada data yang sesuai dengan filter."
                                            : "Belum ada data pelanggaran."}
                                    </td>
                                </tr>
                            ) : (
                                list.map((p) => (
                                    <tr
                                        key={p.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3 text-xs">
                                            {formatTanggal(p.tanggal)}
                                        </td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {p.siswa?.nama ?? "-"}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {p.siswa?.kelas ?? ""} •{" "}
                                                {p.siswa?.nisn ?? ""}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            {p.jenis_pelanggaran}
                                        </td>
                                        <td className="p-3">
                                            <span
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${poinColor(p.poin)}`}
                                            >
                                                {p.poin} pts
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <select
                                                value={p.status}
                                                onChange={(e) =>
                                                    updateStatus(
                                                        p.id,
                                                        e.target.value,
                                                    )
                                                }
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${statusBadge(p.status)}`}
                                            >
                                                <option value="dicatat">
                                                    Dicatat
                                                </option>
                                                <option value="diproses">
                                                    Diproses
                                                </option>
                                                <option value="selesai">
                                                    Selesai
                                                </option>
                                            </select>
                                        </td>
                                        <td className="p-3">
                                            {p.foto_bukti ? (
                                                <a
                                                    href={p.foto_url}
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
                                                onClick={() => remove(p.id)}
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
                        {pelanggaran?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(pelanggaran.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {pelanggaran?.current_page ?? 1} dari{" "}
                        {pelanggaran?.last_page ?? 1}
                    </span>
                    <div>
                        {pelanggaran?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(pelanggaran.next_page_url)
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
