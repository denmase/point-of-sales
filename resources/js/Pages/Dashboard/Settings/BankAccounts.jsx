import React, { useState, useEffect } from "react";
import { Head, useForm, usePage, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Input from "@/Components/Dashboard/Input";
import {
    IconBuildingBank,
    IconPlus,
    IconPencil,
    IconTrash,
    IconCheck,
    IconX,
    IconGripVertical,
} from "@tabler/icons-react";
import toast from "react-hot-toast";

export default function BankAccounts({ bankAccounts = [] }) {
    const { flash } = usePage().props;
    const [showForm, setShowForm] = useState(false);
    const [editingId, setEditingId] = useState(null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        bank_name: "",
        account_number: "",
        account_name: "",
        logo: null,
        is_active: true,
    });

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    const handleSubmit = (e) => {
        e.preventDefault();
        const options = {
            forceFormData: true,
            onSuccess: () => {
                reset();
                setEditingId(null);
                setShowForm(false);
            },
        };

        if (editingId) {
            put(route("settings.bank-accounts.update", editingId), options);
        } else {
            post(route("settings.bank-accounts.store"), options);
        }
    };

    const handleEdit = (bank) => {
        setData({
            bank_name: bank.bank_name,
            account_number: bank.account_number,
            account_name: bank.account_name,
            logo: null,
            is_active: bank.is_active,
        });
        setEditingId(bank.id);
        setShowForm(true);
    };

    const handleDelete = (bank) => {
        if (confirm(`Hapus rekening ${bank.bank_name}?`)) {
            router.delete(route("settings.bank-accounts.destroy", bank.id));
        }
    };

    const handleToggle = (bank) => {
        router.patch(route("settings.bank-accounts.toggle", bank.id));
    };

    const handleCancel = () => {
        reset();
        setEditingId(null);
        setShowForm(false);
    };

    return (
        <>
            <Head title="Pengaturan Rekening Bank" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconBuildingBank size={28} className="text-primary-500" />
                    Rekening Bank
                </h1>
                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Kelola rekening bank untuk pembayaran transfer
                </p>
            </div>

            <div className="max-w-3xl space-y-6">
                {/* Bank List */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div className="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <h3 className="font-semibold text-slate-800 dark:text-white">
                            Daftar Rekening ({bankAccounts.length})
                        </h3>
                        {!showForm && (
                            <button
                                onClick={() => setShowForm(true)}
                                className="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium transition-colors"
                            >
                                <IconPlus size={18} />
                                Tambah Bank
                            </button>
                        )}
                    </div>

                    {/* Add/Edit Form */}
                    {showForm && (
                        <form
                            onSubmit={handleSubmit}
                            className="p-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200 dark:border-slate-700"
                        >
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <Input
                                    label="Nama Bank"
                                    placeholder="BCA, Mandiri, BNI..."
                                    value={data.bank_name}
                                    onChange={(e) =>
                                        setData("bank_name", e.target.value)
                                    }
                                    errors={errors.bank_name}
                                />
                                <Input
                                    label="Nomor Rekening"
                                    placeholder="1234567890"
                                    value={data.account_number}
                                    onChange={(e) =>
                                        setData(
                                            "account_number",
                                            e.target.value
                                        )
                                    }
                                    errors={errors.account_number}
                                />
                                <Input
                                    label="Atas Nama"
                                    placeholder="Nama pemilik rekening"
                                    value={data.account_name}
                                    onChange={(e) =>
                                        setData("account_name", e.target.value)
                                    }
                                    errors={errors.account_name}
                                />
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div className="md:col-span-1">
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Logo Bank (opsional)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        onChange={(e) =>
                                            setData(
                                                "logo",
                                                e.target.files?.[0] || null
                                            )
                                        }
                                        className="w-full h-11 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                    />
                                    {errors.logo && (
                                        <p className="text-xs text-danger-500 mt-1">
                                            {errors.logo}
                                        </p>
                                    )}
                                </div>
                                <div className="md:col-span-2">
                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                        Format: JPG, PNG, SVG. Maks 1MB. Jika dibiarkan kosong, ikon default akan digunakan.
                                    </p>
                                </div>
                            </div>
                            <div className="flex items-center gap-3">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium transition-colors disabled:opacity-50"
                                >
                                    <IconCheck size={18} />
                                    {editingId ? "Update" : "Simpan"}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleCancel}
                                    className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm font-medium hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                >
                                    <IconX size={18} />
                                    Batal
                                </button>
                            </div>
                        </form>
                    )}

                    {/* Bank List */}
                    {bankAccounts.length > 0 ? (
                        <div className="divide-y divide-slate-200 dark:divide-slate-800">
                            {bankAccounts.map((bank) => (
                                <div
                                    key={bank.id}
                                    className={`p-4 flex items-center gap-4 ${
                                        !bank.is_active ? "opacity-50" : ""
                                    }`}
                                >
                                    <div className="text-slate-400 cursor-move">
                                        <IconGripVertical size={20} />
                                    </div>
                                    <div className="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center overflow-hidden">
                                        {bank.logo_url ? (
                                            <img
                                                src={bank.logo_url}
                                                alt={bank.bank_name}
                                                className="max-w-full max-h-full object-contain"
                                            />
                                        ) : (
                                            <IconBuildingBank
                                                size={24}
                                                className="text-slate-500"
                                            />
                                        )}
                                    </div>
                                    <div className="flex-1">
                                        <p className="font-semibold text-slate-800 dark:text-white">
                                            {bank.bank_name}
                                        </p>
                                        <p className="text-sm text-slate-500 dark:text-slate-400">
                                            {bank.account_number} •{" "}
                                            {bank.account_name}
                                        </p>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        {/* Toggle Active */}
                                        <button
                                            onClick={() => handleToggle(bank)}
                                            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                                                bank.is_active
                                                    ? "bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400"
                                                    : "bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                            }`}
                                        >
                                            {bank.is_active
                                                ? "Aktif"
                                                : "Nonaktif"}
                                        </button>
                                        {/* Edit */}
                                        <button
                                            onClick={() => handleEdit(bank)}
                                            className="p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                        >
                                            <IconPencil size={18} />
                                        </button>
                                        {/* Delete */}
                                        <button
                                            onClick={() => handleDelete(bank)}
                                            className="p-2 rounded-lg text-danger-500 hover:bg-danger-50 dark:hover:bg-danger-900/20 transition-colors"
                                        >
                                            <IconTrash size={18} />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center">
                            <IconBuildingBank
                                size={48}
                                className="mx-auto text-slate-300 dark:text-slate-600 mb-3"
                            />
                            <p className="text-slate-500 dark:text-slate-400">
                                Belum ada rekening bank
                            </p>
                        </div>
                    )}
                </div>

                {/* Info */}
                <div className="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
                    <p className="text-sm text-blue-700 dark:text-blue-300">
                        <strong>Tips:</strong> Aktifkan "Transfer Bank" di
                        halaman{" "}
                        <a
                            href={route("settings.payments.edit")}
                            className="underline"
                        >
                            Payment Gateway
                        </a>{" "}
                        agar metode pembayaran ini muncul di kasir.
                    </p>
                </div>
            </div>
        </>
    );
}

BankAccounts.layout = (page) => <DashboardLayout children={page} />;
