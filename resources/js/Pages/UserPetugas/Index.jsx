import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, router, usePage } from "@inertiajs/react";
import { useState } from "react";

export default function UserPetugasIndex(props) {
    const flash = usePage().props.flash;
    const [showForm, setShowForm] = useState(false);
    const [editId, setEditId] = useState(null);
    const [resetId, setResetId] = useState(null);
    const [form, setForm] = useState({
        name: "",
        email: "",
        password: "",
        role: "petugas",
    });
    const [resetForm, setResetForm] = useState({ password: "" });

    const submit = (e) => {
        e.preventDefault();
        if (editId) {
            router.patch(
                route("user-petugas.update", editId),
                {
                    name: form.name,
                    email: form.email,
                    role: form.role,
                },
                {
                    onSuccess: () => {
                        setShowForm(false);
                        setEditId(null);
                        setForm({
                            name: "",
                            email: "",
                            password: "",
                            role: "petugas",
                        });
                    },
                },
            );
        } else {
            router.post(route("user-petugas.store"), form, {
                onSuccess: () => {
                    setShowForm(false);
                    setForm({
                        name: "",
                        email: "",
                        password: "",
                        role: "petugas",
                    });
                },
            });
        }
    };

    const edit = (u) => {
        setEditId(u.id);
        setForm({ name: u.name, email: u.email, password: "", role: u.role });
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
                        setForm({
                            name: "",
                            email: "",
                            password: "",
                            role: "petugas",
                        });
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
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Nama
                                </label>
                                <input
                                    value={form.name}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            name: e.target.value,
                                        })
                                    }
                                    required
                                    className="w-full rounded-lg border-gray-300"
                                />
                            </div>
                            <div>
                                <label className="mb-1 block text-xs font-semibold">
                                    Email
                                </label>
                                <input
                                    type="email"
                                    value={form.email}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            email: e.target.value,
                                        })
                                    }
                                    required
                                    className="w-full rounded-lg border-gray-300"
                                />
                            </div>
                            {!editId && (
                                <div>
                                    <label className="mb-1 block text-xs font-semibold">
                                        Password
                                    </label>
                                    <input
                                        type="password"
                                        value={form.password}
                                        onChange={(e) =>
                                            setForm({
                                                ...form,
                                                password: e.target.value,
                                            })
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
                                        setForm({
                                            ...form,
                                            role: e.target.value,
                                        })
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
                        <button className="mt-4 rounded-lg bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                            {editId ? "💾 Simpan Perubahan" : "➕ Buat Akun"}
                        </button>
                    </form>
                )}

                <div className="rounded-lg bg-white p-6 shadow">
                    <h3 className="mb-4 font-semibold">Daftar Akun</h3>
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b text-left text-gray-500">
                                <th className="py-2">Nama</th>
                                <th className="py-2">Email</th>
                                <th className="py-2">Role</th>
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
