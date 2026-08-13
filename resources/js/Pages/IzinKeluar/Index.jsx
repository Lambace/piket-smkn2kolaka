import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState, useEffect } from "react";

const today = new Date().toISOString().split("T")[0];

const emptyForm = {
    siswa_id: "",
    tanggal: today,
    jam_keluar: "",
    jenis: "Sakit",
    keterangan: "",
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

export default function Index({ izinKeluar, daftarSiswa, params = {} }) {
    const { flash, errors } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [form, setForm] = useState(emptyForm);
    const [formKelas, setFormKelas] = useState("");
    const [filterTgl, setFilterTgl] = useState(params.tanggal ?? "");
    const [searchTerm, setSearchTerm] = useState(params.search ?? "");

    const list = Array.isArray(izinKeluar?.data) ? izinKeluar.data : [];
    const siswaList = Array.isArray(daftarSiswa) ? daftarSiswa : [];

    // Filter kelas untuk dropdown siswa
    const kelasOptions = [...new Set(siswaList.map((s) => s.kelas))].sort();
    const siswaFiltered = formKelas
        ? siswaList.filter((s) => s.kelas === formKelas)
        : siswaList;

    const inputClass =
        "mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500";

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm({ ...form, [name]: value });
    };

    const submit = (e, kirimNotif = false) => {
        e.preventDefault();
        router.post(
            route("izin-keluar.store"),
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
        router.put(route("izin-keluar.update", id), { status });
    };

    const remove = (id) => {
        if (confirm("Hapus data izin keluar ini?")) {
            router.delete(route("izin-keluar.destroy", id));
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
                    route("izin-keluar.index"),
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
            case "disetujui":
                return "bg-green-100 text-green-700";
            case "ditolak":
                return "bg-red-100 text-red-700";
            case "kembali":
                return "bg-blue-100 text-blue-700";
            default:
                return "bg-yellow-100 text-yellow-700";
        }
    };

    const statusOptions = ["menunggu", "disetujui", "ditolak", "kembali"];

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Izin Keluar
                </h2>
            }
        >
            <Head title="Izin Keluar" />

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
                                        route("izin-keluar.index"),
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
                        {showForm ? "Tutup Form" : "+ Catat Izin Keluar"}
                    </button>
                </div>

                {/* Form */}
                {showForm && (
                    <form
                        onSubmit={submit}
                        className="space-y-4 rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="text-lg font-semibold text-gray-800">
                            Catat Izin Keluar
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
                                    Jam Keluar *
                                </label>
                                <input
                                    type="time"
                                    name="jam_keluar"
                                    value={form.jam_keluar}
                                    onChange={handleChange}
                                    className={inputClass}
                                    required
                                />
                            </div>
                            <div>
                                <label className="text-sm text-gray-600">
                                    Jenis Izin *
                                </label>
                                <select
                                    name="jenis"
                                    value={form.jenis}
                                    onChange={handleChange}
                                    className={inputClass}
                                >
                                    <option value="Sakit">Sakit</option>
                                    <option value="Kepentingan Keluarga">
                                        Kepentingan Keluarga
                                    </option>
                                    <option value="Keperluan Medis">
                                        Keperluan Medis
                                    </option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
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
                                    placeholder="Mis: Sakit perut, perlu ke dokter..."
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
                                <th className="p-3">Jam Keluar</th>
                                <th className="p-3">Jam Kembali</th>
                                <th className="p-3">Jenis</th>
                                <th className="p-3">Status</th>
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
                                            : "Belum ada data izin keluar."}
                                    </td>
                                </tr>
                            ) : (
                                list.map((i) => (
                                    <tr
                                        key={i.id}
                                        className="border-t border-gray-200"
                                    >
                                        <td className="p-3 text-xs">
                                            {formatTanggal(i.tanggal)}
                                        </td>
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {i.siswa?.nama ?? "-"}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {i.siswa?.kelas ?? ""} •{" "}
                                                {i.siswa?.nisn ?? ""}
                                            </div>
                                        </td>
                                        <td className="p-3 font-mono text-sm">
                                            {i.jam_keluar}
                                        </td>
                                        <td className="p-3 font-mono text-sm">
                                            {i.jam_kembali ?? "-"}
                                        </td>
                                        <td className="p-3">
                                            <span className="rounded-md bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">
                                                {i.jenis}
                                            </span>
                                        </td>
                                        <td className="p-3">
                                            <select
                                                value={i.status}
                                                onChange={(e) =>
                                                    updateStatus(
                                                        i.id,
                                                        e.target.value,
                                                    )
                                                }
                                                className={`rounded-md px-2 py-1 text-xs font-semibold ${statusBadge(i.status)}`}
                                            >
                                                {statusOptions.map((opt) => (
                                                    <option
                                                        key={opt}
                                                        value={opt}
                                                    >
                                                        {opt}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                        <td className="p-3 text-center">
                                            <button
                                                onClick={() => remove(i.id)}
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
                        {izinKeluar?.prev_page_url && (
                            <button
                                onClick={() =>
                                    router.get(izinKeluar.prev_page_url)
                                }
                                className="rounded-md bg-gray-200 px-3 py-1 text-sm hover:bg-gray-300"
                            >
                                ← Sebelumnya
                            </button>
                        )}
                    </div>
                    <span className="text-sm text-gray-500">
                        Halaman {izinKeluar?.current_page ?? 1} dari{" "}
                        {izinKeluar?.last_page ?? 1}
                    </span>
                    <div>
                        {izinKeluar?.next_page_url && (
                            <button
                                onClick={() =>
                                    router.get(izinKeluar.next_page_url)
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
