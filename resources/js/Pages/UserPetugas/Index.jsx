import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

// ===== Data Dropdown =====
   const GOLONGAN_OPTIONS = [
       { group: "Golongan I", items: ["I/a", "I/b", "I/c", "I/d"] },
       { group: "Golongan II", items: ["II/a", "II/b", "II/c", "II/d"] },
       { group: "Golongan III", items: ["III/a", "III/b", "III/c", "III/d"] },
       {
           group: "Golongan IV",
           items: ["IV/a", "IV/b", "IV/c", "IV/d", "IV/e"],
       },
       {
           group: "Golongan V - XVII (PPPK)",
           items: ["V","VI","VII","VIII","IX","X","XI","XII","XIII","XIV","XV","XVI","XVII",],
       },
   ];
const defaultForm = {
    name: "",
    email: "",
    password: "",
    role: "petugas",
    jenis_kelamin: "",
    nip: "",
    golongan: "",
    status_kepegawaian: "",
};

export default function UserPetugasIndex(props) {
    const flash = usePage().props.flash;
    const [showForm, setShowForm] = useState(false);
    const [editId, setEditId] = useState(null);
    const [resetId, setResetId] = useState(null);
    const [form, setForm] = useState(defaultForm);
    const [resetForm, setResetForm] = useState({ password: "" });

    const updateField = (field, value) =>
        setForm((prev) => ({ ...prev, [field]: value }));

    const submit = (e) => {
        e.preventDefault();

        // ===== Payload LENGKAP (dipakai POST & PATCH) =====
        const payload = {
            name: form.name,
            email: form.email,
            role: form.role,
            jenis_kelamin: form.jenis_kelamin,
            nip: form.nip,
            golongan: form.golongan,
            status_kepegawaian: form.status_kepegawaian,
        };

        if (editId) {
            router.patch(route("user-petugas.update", editId), payload, {
                onSuccess: () => {
                    setShowForm(false);
                    setEditId(null);
                    setForm(defaultForm);
                },
            });
        } else {
            router.post(
                route("user-petugas.store"),
                { ...payload, password: form.password },
                {
                    onSuccess: () => {
                        setShowForm(false);
                        setForm(defaultForm);
                    },
                },
            );
        }
    };

    const edit = (u) => {
        setEditId(u.id);
        setForm({
            name: u.name || "",
            email: u.email || "",
            password: "",
            role: u.role || "petugas",
            jenis_kelamin: u.jenis_kelamin || "",
            nip: u.nip || "",
            golongan: u.golongan || "",
            status_kepegawaian: u.status_kepegawaian || "",
        });
        setShowForm(true);
    };

    const resetPassword = (e) => {
        e.preventDefault();
        router.post(route("user-petugas.reset-password", resetId), resetForm, {
            onSuccess: () => {
                setResetId(null);
                setResetForm({ password: "" });
            },
        });
    };

    const hapus = (id) => {
        if (confirm("Hapus akun ini?"))
            router.delete(route("user-petugas.destroy", id));
    };

    // Helper: label jenis kelamin
    const jkLabel = (jk) =>
        jk === "L" ? "Laki-laki" : jk === "P" ? "Perempuan" : "-";

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold text-gray-800">
                    👥 Manajemen Akun Petugas Piket
                </h2>
            }
        >
            <Head title="Akun Petugas" />

            <div className="space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800">
                        ✅ {flash.success}
                    </div>
                )}
                {flash?.error && (
                    <div className="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
                        ❌ {flash.error}
                    </div>
                )}

                <div className="rounded-lg bg-blue-50 p-4 text-sm text-blue-800">
                    <p className="font-semibold">ℹ️ Tentang Role:</p>
                    <ul className="ml-5 mt-1 list-disc space-y-0.5">
                        <li>
                            <b>Koordinator</b> = Admin Piket — akses semua menu,
                            termasuk kelola akun & pengaturan
                        </li>
                        <li>
                            <b>Petugas Piket</b> — akses menu piket harian
                            (Dashboard, Keterlambatan, Izin Keluar, Buku Tamu,
                            Pelanggaran, Laporan)
                        </li>
                    </ul>
                </div>

                <button
                    onClick={() => {
                        setShowForm(!showForm);
                        setEditId(null);
                        setForm(defaultForm);
                    }}
                    className="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                >
                    {showForm ? "✕ Tutup Form" : "+ Tambah Akun Baru"}
                </button>

                {showForm && (
                    <form
                        onSubmit={submit}
                        className="rounded-lg bg-white p-6 shadow"
                    >
                        <h3 className="mb-4 font-semibold">
                            {editId ? "✏️ Edit Akun" : "➕ Buat Akun Baru"}
                        </h3>

                        {/* ===== AKUN LOGIN ===== */}
                        <div className="mb-4 text-xs font-bold uppercase tracking-wider text-gray-500">
                            Akun Login
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Nama <span className="text-red-500">*</span>
                                </label>
                                <input
                                    value={form.name}
                                    onChange={(e) =>
                                        updateField("name", e.target.value)
                                    }
                                    required
                                    className="w-full rounded-lg border-gray-300"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Email{" "}
                                    <span className="text-red-500">*</span>
                                </label>
                                <input
                                    type="email"
                                    value={form.email}
                                    onChange={(e) =>
                                        updateField("email", e.target.value)
                                    }
                                    required
                                    className="w-full rounded-lg border-gray-300"
                                />
                            </div>
                            {!editId && (
                                <div>
                                    <label className="mb-1 block text-xs font-semibold">
                                        Password{" "}
                                        <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="password"
                                        value={form.password}
                                        onChange={(e) =>
                                            updateField(
                                                "password",
                                                e.target.value,
                                            )
                                        }
                                        required
                                        minLength={6}
                                        className="w-full rounded-lg border-gray-300"
                                    />
                                </div>
                            )}
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Role
                                </label>
                                <select
                                    value={form.role}
                                    onChange={(e) =>
                                        updateField("role", e.target.value)
                                    }
                                    className="w-full rounded-lg border-gray-300"
                                >
                                    <option value="petugas">
                                        Petugas Piket
                                    </option>
                                    <option value="koordinator">
                                        Koordinator (Admin Piket)
                                    </option>
                                </select>
                            </div>
                        </div>

                        {/* ===== DATA PEGAWAI ===== */}
                        <div className="mt-6 mb-4 flex items-center gap-2 border-t border-gray-200 pt-4">
                            <span className="text-xs font-bold uppercase tracking-wider text-gray-500">
                                📋 Data Pegawai
                            </span>
                            <span className="text-xs text-gray-400">
                                (opsional — untuk Daftar Hadir Piket)
                            </span>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Jenis Kelamin
                                </label>
                                <select
                                    value={form.jenis_kelamin}
                                    onChange={(e) =>
                                        updateField(
                                            "jenis_kelamin",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border-gray-300"
                                >
                                    <option value="">-- Pilih --</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    NIP
                                </label>
                                <input
                                    type="text"
                                    value={form.nip}
                                    onChange={(e) =>
                                        updateField("nip", e.target.value)
                                    }
                                    placeholder="197501012000031001"
                                    maxLength={20}
                                    className="w-full rounded-lg border-gray-300 font-mono"
                                />
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Golongan
                                </label>
                                <select
                                    value={form.golongan}
                                    onChange={(e) =>
                                        updateField("golongan", e.target.value)
                                    }
                                    className="w-full rounded-lg border-gray-300"
                                >
                                    <option value="">
                                        -- Pilih Golongan --
                                    </option>
                                    {GOLONGAN_OPTIONS.map((group) => (
                                        <optgroup
                                            key={group.group}
                                            label={group.group}
                                        >
                                            {group.items.map((g) => (
                                                <option key={g} value={g}>
                                                    {g}
                                                </option>
                                            ))}
                                        </optgroup>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Status Kepegawaian
                                </label>
                                <select
                                    value={form.status_kepegawaian}
                                    onChange={(e) =>
                                        updateField(
                                            "status_kepegawaian",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border-gray-300"
                                >
                                    <option value="">-- Pilih Status --</option>
                                    <optgroup label="PNS">
                                        <option value="PNS">PNS</option>
                                    </optgroup>
                                    <optgroup label="PPPK">
                                        <option value="PPPK Guru">
                                            PPPK Guru
                                        </option>
                                        <option value="PPPK/PW Guru">
                                            PPPK/PW Guru
                                        </option>
                                        <option value="PPPK/Staf TU">
                                            PPPK/Staf TU
                                        </option>
                                        <option value="PPPK/PW Staf TU">
                                            PPPK/PW Staf TU
                                        </option>
                                    </optgroup>
                                    <optgroup label="Honorer">
                                        <option value="Guru Honorer">
                                            Guru Honorer
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div className="mt-6 flex gap-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setShowForm(false);
                                    setEditId(null);
                                    setForm(defaultForm);
                                }}
                                className="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-300"
                            >
                                Batal
                            </button>
                            <button className="rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                                {editId
                                    ? "💾 Simpan Perubahan"
                                    : "➕ Buat Akun"}
                            </button>
                        </div>
                    </form>
                )}

                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 font-semibold">Daftar Akun</h3>
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b text-left text-gray-500">
                                    <th className="py-2">Nama</th>
                                    <th className="py-2">Email</th>
                                    <th className="py-2">Role</th>
                                    <th className="py-2">JK</th>
                                    <th className="py-2">NIP</th>
                                    <th className="py-2">Gol</th>
                                    <th className="py-2">Status</th>
                                    <th className="py-2 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                {props.users.map((u) => (
                                    <tr key={u.id} className="border-b">
                                        <td className="py-2 font-semibold">
                                            {u.name}
                                        </td>
                                        <td className="py-2">{u.email}</td>
                                        <td className="py-2">
                                            <span
                                                className={
                                                    "rounded-full px-2 py-0.5 text-xs font-semibold " +
                                                    (u.role === "koordinator"
                                                        ? "bg-indigo-100 text-indigo-700"
                                                        : "bg-amber-100 text-amber-700")
                                                }
                                            >
                                                {u.role === "koordinator"
                                                    ? "👑 Koordinator"
                                                    : "🧑 Petugas"}
                                            </span>
                                        </td>
                                        <td className="py-2 text-xs">
                                            {jkLabel(u.jenis_kelamin)}
                                        </td>
                                        <td className="py-2 font-mono text-xs">
                                            {u.nip || "-"}
                                        </td>
                                        <td className="py-2 text-xs">
                                            {u.golongan || "-"}
                                        </td>
                                        <td className="py-2">
                                            {u.status_kepegawaian ? (
                                                <span className="rounded-full bg-teal-100 px-2 py-0.5 text-xs font-semibold text-teal-700">
                                                    {u.status_kepegawaian}
                                                </span>
                                            ) : (
                                                <span className="text-xs text-gray-400">
                                                    -
                                                </span>
                                            )}
                                        </td>
                                        <td className="py-2 text-right">
                                            <button
                                                onClick={() => edit(u)}
                                                className="mr-2 text-xs text-blue-600 hover:underline"
                                            >
                                                Edit
                                            </button>
                                            <button
                                                onClick={() => setResetId(u.id)}
                                                className="mr-2 text-xs text-orange-600 hover:underline"
                                            >
                                                Reset PW
                                            </button>
                                            <button
                                                onClick={() => hapus(u.id)}
                                                className="text-xs text-red-600 hover:underline"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                {resetId && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                        <form
                            onSubmit={resetPassword}
                            className="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl"
                        >
                            <h3 className="mb-4 font-semibold">
                                🔑 Reset Password
                            </h3>
                            <input
                                type="password"
                                placeholder="Password baru"
                                value={resetForm.password}
                                onChange={(e) =>
                                    setResetForm({ password: e.target.value })
                                }
                                required
                                minLength={6}
                                className="mb-3 w-full rounded-lg border-gray-300"
                            />
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setResetId(null)}
                                    className="flex-1 rounded-lg bg-gray-200 px-3 py-2 text-sm"
                                >
                                    Batal
                                </button>
                                <button className="flex-1 rounded-lg bg-orange-600 px-3 py-2 text-sm font-semibold text-white">
                                    Reset
                                </button>
                            </div>
                        </form>
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
