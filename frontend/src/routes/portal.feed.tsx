import { createFileRoute } from "@tanstack/react-router";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { PortalShell } from "@/components/portal/PortalShell";
import { apiFetch, ApiError } from "@/lib/api";
import { Newspaper, Plus, Pencil, Trash2, ImagePlus, Eye, EyeOff } from "lucide-react";
import { useRef, useState } from "react";

export const Route = createFileRoute("/portal/feed")({ component: Feed });

type ArticleTheme = "FOREST" | "WILDLIFE" | "SECURITY" | "SUNSET" | "SKY";

interface NewsArticle {
  article_id: number;
  title: string;
  excerpt: string;
  body: string | null;
  image_url: string | null;
  category: string;
  source: string;
  read_time: string;
  theme: ArticleTheme;
  published: boolean;
  published_at: string | null;
  author: { user_id: string; first_name: string; last_name: string } | null;
}

interface ArticlesPage {
  data: NewsArticle[];
}

const THEMES: ArticleTheme[] = ["FOREST", "WILDLIFE", "SECURITY", "SUNSET", "SKY"];

function emptyDraft() {
  return {
    title: "",
    excerpt: "",
    body: "",
    category: "",
    source: "Uganda Wildlife Authority",
    read_time: "3 min",
    theme: "FOREST" as ArticleTheme,
    published: true,
  };
}

function Feed() {
  const queryClient = useQueryClient();
  const [builderOpen, setBuilderOpen] = useState(false);
  const [editingId, setEditingId] = useState<number | null>(null);
  const [draft, setDraft] = useState(emptyDraft());
  const [saving, setSaving] = useState(false);
  const [err, setErr] = useState<string | null>(null);
  const [uploadingImageFor, setUploadingImageFor] = useState<number | null>(null);
  const fileInputRef = useRef<HTMLInputElement | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ["news-articles"],
    queryFn: () => apiFetch<ArticlesPage | NewsArticle[]>("/news-articles?per_page=50"),
  });
  // Laravel's index() paginates ({ data: [...] }); tolerate either shape rather than assuming.
  const articles = Array.isArray(data) ? data : (data?.data ?? []);

  function openNew() {
    setEditingId(null);
    setDraft(emptyDraft());
    setErr(null);
    setBuilderOpen(true);
  }

  function openEdit(article: NewsArticle) {
    setEditingId(article.article_id);
    setDraft({
      title: article.title,
      excerpt: article.excerpt,
      body: article.body ?? "",
      category: article.category,
      source: article.source,
      read_time: article.read_time,
      theme: article.theme,
      published: article.published,
    });
    setErr(null);
    setBuilderOpen(true);
  }

  async function handleSave() {
    setErr(null);
    if (!draft.title.trim() || !draft.excerpt.trim() || !draft.category.trim()) {
      setErr("Title, excerpt, and category are required.");
      return;
    }

    setSaving(true);
    try {
      if (editingId) {
        await apiFetch(`/news-articles/${editingId}`, {
          method: "PATCH",
          body: JSON.stringify(draft),
        });
      } else {
        await apiFetch("/news-articles", { method: "POST", body: JSON.stringify(draft) });
      }
      await queryClient.invalidateQueries({ queryKey: ["news-articles"] });
      setBuilderOpen(false);
    } catch (e) {
      setErr(e instanceof ApiError ? e.message : "Couldn't save the article.");
    } finally {
      setSaving(false);
    }
  }

  async function togglePublished(article: NewsArticle) {
    try {
      await apiFetch(`/news-articles/${article.article_id}`, {
        method: "PATCH",
        body: JSON.stringify({ published: !article.published }),
      });
      await queryClient.invalidateQueries({ queryKey: ["news-articles"] });
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Couldn't update the article.");
    }
  }

  async function handleDelete(articleId: number) {
    if (!confirm("Delete this article? This removes it from the mobile feed too.")) return;
    try {
      await apiFetch(`/news-articles/${articleId}`, { method: "DELETE" });
      await queryClient.invalidateQueries({ queryKey: ["news-articles"] });
    } catch (e) {
      alert(e instanceof ApiError ? e.message : "Couldn't delete the article.");
    }
  }

  function pickImage(articleId: number) {
    setUploadingImageFor(articleId);
    fileInputRef.current?.click();
  }

  async function handleImageSelected(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    const articleId = uploadingImageFor;
    e.target.value = "";
    if (!file || !articleId) return;

    const form = new FormData();
    form.append("image", file);
    try {
      await apiFetch(`/news-articles/${articleId}/image`, { method: "POST", body: form });
      await queryClient.invalidateQueries({ queryKey: ["news-articles"] });
    } catch (err) {
      alert(err instanceof ApiError ? err.message : "Couldn't upload the image.");
    } finally {
      setUploadingImageFor(null);
    }
  }

  return (
    <PortalShell
      title="Community Feed"
      subtitle="Compose the articles shown on the mobile app's Community News feed."
      actions={
        <button className="portal-btn portal-btn-gold" onClick={openNew}>
          <Plus size={13} /> New article
        </button>
      }
    >
      <input
        ref={fileInputRef}
        type="file"
        accept="image/*"
        className="hidden"
        onChange={handleImageSelected}
      />

      {isLoading && (
        <div className="portal-card p-6 text-sm text-[var(--p-ink-soft)]">Loading articles…</div>
      )}

      {!isLoading && articles.length === 0 && (
        <div className="portal-card p-8 text-center text-[13px] text-[var(--p-ink-soft)]">
          <Newspaper className="mx-auto mb-2 text-[var(--p-olive)]" size={28} />
          No articles yet. Publish one to populate the mobile Community News feed.
        </div>
      )}

      <div className="grid grid-cols-2 gap-4">
        {articles.map((a) => (
          <div key={a.article_id} className="portal-card overflow-hidden">
            <div className="h-28 relative bg-[var(--p-olive-soft,#e8e8dc)]">
              {a.image_url ? (
                <img src={a.image_url} alt="" className="h-full w-full object-cover" />
              ) : (
                <div className="h-full w-full flex items-center justify-center text-[var(--p-ink-soft)] text-[11px] font-semibold uppercase tracking-wider">
                  {a.theme}
                </div>
              )}
              <button
                className="absolute bottom-2 right-2 portal-btn portal-btn-ghost text-[11px] px-2 bg-white/90"
                onClick={() => pickImage(a.article_id)}
                disabled={uploadingImageFor === a.article_id}
              >
                <ImagePlus size={12} />
                {uploadingImageFor === a.article_id ? "Uploading…" : "Image"}
              </button>
            </div>
            <div className="p-4">
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0">
                  <div className="font-semibold text-[14px] truncate">{a.title}</div>
                  <div className="text-[12px] text-[var(--p-ink-soft)] mt-0.5 line-clamp-2">
                    {a.excerpt}
                  </div>
                </div>
                <span
                  className={`shrink-0 inline-flex items-center gap-1 text-[11px] font-bold px-2 py-1 rounded-lg ${
                    a.published
                      ? "bg-emerald-50 text-emerald-700"
                      : "bg-neutral-100 text-neutral-500"
                  }`}
                >
                  {a.published ? <Eye size={11} /> : <EyeOff size={11} />}
                  {a.published ? "Published" : "Draft"}
                </span>
              </div>
              <div className="mt-3 flex flex-wrap gap-1.5">
                <span className="portal-chip text-[10px]">{a.category}</span>
                <span className="portal-chip text-[10px]">{a.read_time}</span>
              </div>
              <div className="mt-3 flex items-center justify-between text-[11px] text-[var(--p-ink-soft)]">
                <span>{a.source}</span>
                <div className="flex items-center gap-2">
                  <button
                    className="portal-btn portal-btn-ghost text-[11px] px-2"
                    onClick={() => togglePublished(a)}
                  >
                    {a.published ? "Unpublish" : "Publish"}
                  </button>
                  <button
                    className="portal-btn portal-btn-ghost text-[11px] px-2"
                    onClick={() => openEdit(a)}
                  >
                    <Pencil size={11} /> Edit
                  </button>
                  <button
                    className="portal-btn portal-btn-ghost text-[11px] px-2 text-[var(--p-danger)]"
                    onClick={() => handleDelete(a.article_id)}
                  >
                    <Trash2 size={11} /> Delete
                  </button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>

      {builderOpen && (
        <div
          className="fixed inset-0 bg-black/40 grid place-items-center z-50 p-4"
          onClick={() => setBuilderOpen(false)}
        >
          <div
            className="bg-white rounded-xl w-full max-w-xl max-h-[88vh] overflow-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="p-5 border-b border-[var(--p-olive-line)]">
              <h3 className="portal-display text-lg font-bold">
                {editingId ? "Edit article" : "New article"}
              </h3>
              <p className="text-[12px] text-[var(--p-ink-soft)] mt-0.5">
                Published articles sync to the mobile app's feed within moments.
              </p>
            </div>
            <div className="p-5 space-y-4">
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                  Title
                </span>
                <input
                  className="portal-input mt-1"
                  value={draft.title}
                  onChange={(e) => setDraft((d) => ({ ...d, title: e.target.value }))}
                  placeholder="e.g. New Elephant Herd Spotted Near Nkuringo"
                />
              </label>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                  Excerpt
                </span>
                <textarea
                  className="portal-input mt-1"
                  rows={2}
                  value={draft.excerpt}
                  onChange={(e) => setDraft((d) => ({ ...d, excerpt: e.target.value }))}
                />
              </label>
              <label className="block">
                <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                  Body (optional)
                </span>
                <textarea
                  className="portal-input mt-1"
                  rows={5}
                  value={draft.body}
                  onChange={(e) => setDraft((d) => ({ ...d, body: e.target.value }))}
                />
              </label>
              <div className="grid grid-cols-2 gap-3">
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                    Category
                  </span>
                  <input
                    className="portal-input mt-1"
                    value={draft.category}
                    onChange={(e) => setDraft((d) => ({ ...d, category: e.target.value }))}
                    placeholder="e.g. Conservation"
                  />
                </label>
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                    Theme
                  </span>
                  <select
                    className="portal-input mt-1"
                    value={draft.theme}
                    onChange={(e) =>
                      setDraft((d) => ({ ...d, theme: e.target.value as ArticleTheme }))
                    }
                  >
                    {THEMES.map((t) => (
                      <option key={t} value={t}>
                        {t}
                      </option>
                    ))}
                  </select>
                </label>
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                    Source
                  </span>
                  <input
                    className="portal-input mt-1"
                    value={draft.source}
                    onChange={(e) => setDraft((d) => ({ ...d, source: e.target.value }))}
                  />
                </label>
                <label className="block">
                  <span className="text-[11px] font-semibold uppercase tracking-wider text-[var(--p-ink-soft)]">
                    Read time
                  </span>
                  <input
                    className="portal-input mt-1"
                    value={draft.read_time}
                    onChange={(e) => setDraft((d) => ({ ...d, read_time: e.target.value }))}
                  />
                </label>
              </div>
              <label className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={draft.published}
                  onChange={(e) => setDraft((d) => ({ ...d, published: e.target.checked }))}
                />
                <span className="text-[12px] text-[var(--p-ink-soft)]">
                  Published (visible on the mobile feed immediately)
                </span>
              </label>

              {err && (
                <div className="text-[12px] text-[var(--p-danger)] bg-[var(--p-danger)]/10 border border-[var(--p-danger)]/30 rounded-md px-3 py-2">
                  {err}
                </div>
              )}
            </div>
            <div className="p-5 border-t border-[var(--p-olive-line)] flex items-center justify-end gap-2">
              <button className="portal-btn portal-btn-ghost" onClick={() => setBuilderOpen(false)}>
                Cancel
              </button>
              <button className="portal-btn" disabled={saving} onClick={handleSave}>
                {saving ? "Saving…" : "Save article"}
              </button>
            </div>
          </div>
        </div>
      )}
    </PortalShell>
  );
}
