import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell, StatusBadge } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import { FileText, Plus, Image as ImageIcon, Type, AlignLeft, Hash, Calendar, List, Trash2, GripVertical, Pencil } from "lucide-react";
import { useState } from "react";

export const Route = createFileRoute("/portal/forms")({ component: Forms });

type FieldType = "text" | "textarea" | "number" | "date" | "select" | "image";

interface FormField {
  field_id?: number;
  label: string;
  field_type: FieldType;
  options?: string[] | null;
  is_required: boolean;
}

interface EvidenceForm {
  form_id: number;
  title: string;
  description: string | null;
  status: "Draft" | "Published";
  fields: FormField[];
  submissions_count?: number;
  updated_at: string;
}

const FIELD_TYPE_META: Record<FieldType, { label: string; icon: any }> = {
  text: { label: "Short text", icon: Type },
  textarea: { label: "Paragraph", icon: AlignLeft },
  number: { label: "Number", icon: Hash },
  date: { label: "Date", icon: Calendar },
  select: { label: "Multiple choice", icon: List },
  image: { label: "Photo evidence", icon: ImageIcon },
};

function emptyField(): FormField {
  return { label: "", field_type: "text", is_required: false };
}

function Forms() {
  const queryClient = useQueryClient();
  const [builderOpen, setBuilderOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [status, setStatus] = useState<"Draft" | "Published">("Draft");
  const [fields, setFields] = useState<FormField[]>([emptyField()]);
  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState<string | null>(null);

  const { data: forms, isLoading } = useQuery({
    queryKey: ["gamepark-forms"],
    queryFn: () => apiFetch<EvidenceForm[]>("/gamepark/forms"),
  });

  function openNew() {
    setEditingId(null);
    setTitle("");
    setDescription("");
    setStatus("Draft");
    setFields([emptyField()]);
    setErr(null);
    setBuilderOpen(true);
  }

  function openEdit(form: EvidenceForm) {
    setEditingId(form.form_id);
    setTitle(form.title);
    setDescription(form.description ?? "");
    setStatus(form.status);
    setFields(form.fields.length ? form.fields.map((f) => ({ ...f })) : [emptyField()]);
    setErr(null);
    setBuilderOpen(true);
  }

  function updateField(i: number, patch: Partial<FormField>) {
    setFields((prev) => prev.map((f, idx) => (idx === i ? { ...f, ...patch } : f)));
  }

  function addField() {
    setFields((prev) => [...prev, emptyField()]);
  }

  function removeField(i: number) {
    setFields((prev) => prev.filter((_, idx) => idx !== i));
  }

  function moveField(i: number, dir: -1 | 1) {
    setFields((prev) => {
      const next = [...prev];
      const target = i + dir;
      if (target < 0 || target >= next.length) return prev;
      [next[i], next[target]] = [next[target], next[i]];
      return next;
    });
  }

  async function handleSave() {
    setErr(null);
    if (!title.trim()) { setErr("Give the form a title."); return; }
    const cleanFields = fields.filter((f) => f.label.trim());
    if (cleanFields.length === 0) { setErr("Add at least one field."); return; }

    setSaving(true);
    try {
      const payload = {
        title,
        description: description || null,
        status,
        fields: cleanFields.map((f) => ({
          label: f.label,
          field_type: f.field_type,
          options: f.field_type === "select" ? (f.options ?? []) : null,
          is_required: f.is_required,
        })),
      };
      if (editingId) {
        await apiFetch(`/gamepark/forms/${editingId}`, { method: "PATCH", body: JSON.stringify(payload) });
      } else {
        await apiFetch("/gamepark/forms", { method: "POST", body: JSON.stringify(payload) });
      }
      await queryClient.invalidateQueries({ queryKey: ["gamepark-forms"] });
      setBuilderOpen(false);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't save the form.");
    } finally {
      setSaving(false);
    }
  }

  async function handleDelete(formId: number) {
    if (!confirm("Delete this form? This cannot be undone.")) return;
    try {
      await apiFetch(`/gamepark/forms/${formId}`, { method: "DELETE" });
      await queryClient.invalidateQueries({ queryKey: ["gamepark-forms"] });
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Couldn't delete the form.");
    }
  }

  return (
    <PortalShell
      title="Forms"
      subtitle="Create, view, and edit evidence forms — Google Forms-style, including photo evidence fields."
      actions={<button className="portal-btn portal-btn-gold" onClick={openNew}><Plus size={13} /> New form</button>}
    >
      {isLoading && <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading forms…</div>}

      {!isLoading && (forms ?? []).length === 0 && (
        <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">
          <FileText className="mx-auto mb-2 text-[var(--p-olive)]" size={28} />
          No forms yet. Create one to start collecting evidence from residents.
        </div>
      )}

      <div className="grid grid-cols-2 gap-4">
        {(forms ?? []).map((f) => (
          <div key={f.form_id} className="portal-card p-4">
            <div className="flex items-start justify-between gap-2">
              <div className="min-w-0">
                <div className="font-semibold text-[14px] truncate">{f.title}</div>
                {f.description && <div className="text-[12px] text-[var(--p-ink-soft)] mt-0.5 line-clamp-2">{f.description}</div>}
              </div>
              <StatusBadge status={f.status} />
            </div>
            <div className="mt-3 flex flex-wrap gap-1.5">
              {f.fields.map((field, i) => {
                const meta = FIELD_TYPE_META[field.field_type];
                return (
                  <span key={i} className="portal-chip text-[10px]"><meta.icon size={10} /> {field.label || meta.label}</span>
                );
              })}
            </div>
            <div className="mt-3 flex items-center justify-between text-[11px] text-[var(--p-ink-soft)]">
              <span>{f.submissions_count ?? 0} submission{(f.submissions_count ?? 0) === 1 ? "" : "s"}</span>
              <div className="flex items-center gap-2">
                <button className="portal-btn portal-btn-ghost text-[11px] px-2" onClick={() => openEdit(f)}><Pencil size={11} /> Edit</button>
                <button className="portal-btn portal-btn-ghost text-[11px] px-2 text-[var(--p-danger)]" onClick={() => handleDelete(f.form_id)}><Trash2 size={11} /> Delete</button>
              </div>
            </div>
          </div>
        ))}
      </div>

      {builderOpen && (
        <div className="fixed inset-0 bg-black/40 grid place-items-center z-50 p-4" onClick={() => setBuilderOpen(false)}>
          <div className="bg-white rounded-xl w-full max-w-2xl max-h-[88vh] overflow-auto" onClick={(e) => e.stopPropagation()}>
            <div className="p-5 border-b border-[var(--p-olive-line)]">
              <h3 className="portal-display text-lg font-bold">{editingId ? "Edit form" : "New form"}</h3>
              <p className="text-[12px] text-[var(--p-ink-soft)] mt-0.5">Add fields the way you would in Google Forms — including a photo field for evidence.</p>
            </div>
            <div className="p-5 space-y-4">
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Title</span>
                <input className="portal-input mt-1" value={title} onChange={(e) => setTitle(e.target.value)} placeholder="e.g. Human-Wildlife Conflict Evidence Report" />
              </label>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Description (optional)</span>
                <textarea className="portal-input mt-1" rows={2} value={description} onChange={(e) => setDescription(e.target.value)} />
              </label>
              <div className="flex items-center gap-2">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Status</span>
                <select className="portal-input w-40" value={status} onChange={(e) => setStatus(e.target.value as "Draft" | "Published")}>
                  <option value="Draft">Draft</option>
                  <option value="Published">Published</option>
                </select>
              </div>

              <div className="border-t border-[var(--p-olive-line)] pt-4">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">Fields</span>
                <div className="mt-2 space-y-2">
                  {fields.map((field, i) => (
                    <div key={i} className="border border-[var(--p-olive-line)] rounded-md p-3">
                      <div className="flex items-center gap-2">
                        <GripVertical size={14} className="text-[var(--p-ink-soft)] shrink-0" />
                        <input
                          className="portal-input flex-1"
                          placeholder="Question / field label"
                          value={field.label}
                          onChange={(e) => updateField(i, { label: e.target.value })}
                        />
                        <select
                          className="portal-input w-40"
                          value={field.field_type}
                          onChange={(e) => updateField(i, { field_type: e.target.value as FieldType })}
                        >
                          {Object.entries(FIELD_TYPE_META).map(([type, meta]) => (
                            <option key={type} value={type}>{meta.label}</option>
                          ))}
                        </select>
                      </div>
                      {field.field_type === "select" && (
                        <input
                          className="portal-input mt-2 text-[12px]"
                          placeholder="Choices, comma separated (e.g. Crop damage, Livestock loss)"
                          value={(field.options ?? []).join(", ")}
                          onChange={(e) => updateField(i, { options: e.target.value.split(",").map((s) => s.trim()).filter(Boolean) })}
                        />
                      )}
                      {field.field_type === "image" && (
                        <div className="mt-2 text-[11px] text-[var(--p-ink-soft)] flex items-center gap-1.5">
                          <ImageIcon size={12} /> Filer will attach a photo here as evidence.
                        </div>
                      )}
                      <div className="mt-2 flex items-center justify-between">
                        <label className="flex items-center gap-1.5 text-[11px] text-[var(--p-ink-soft)]">
                          <input type="checkbox" checked={field.is_required} onChange={(e) => updateField(i, { is_required: e.target.checked })} />
                          Required
                        </label>
                        <div className="flex items-center gap-1">
                          <button className="text-[11px] text-[var(--p-ink-soft)] hover:text-[var(--p-olive-deep)] px-1" onClick={() => moveField(i, -1)} disabled={i === 0}>↑</button>
                          <button className="text-[11px] text-[var(--p-ink-soft)] hover:text-[var(--p-olive-deep)] px-1" onClick={() => moveField(i, 1)} disabled={i === fields.length - 1}>↓</button>
                          <button className="text-[11px] text-[var(--p-danger)] px-1" onClick={() => removeField(i)}><Trash2 size={12} /></button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
                <button className="portal-btn portal-btn-ghost text-[12px] mt-2" onClick={addField}><Plus size={12} /> Add field</button>
              </div>

              {err && <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">{err}</div>}
            </div>
            <div className="p-5 border-t border-[var(--p-olive-line)] flex items-center justify-end gap-2">
              <button className="portal-btn portal-btn-ghost" onClick={() => setBuilderOpen(false)}>Cancel</button>
              <button className="portal-btn" disabled={saving} onClick={handleSave}>{saving ? "Saving…" : "Save form"}</button>
            </div>
          </div>
        </div>
      )}
    </PortalShell>
  );
}
