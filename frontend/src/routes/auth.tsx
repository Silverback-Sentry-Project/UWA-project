import { createFileRoute, Link, useNavigate } from "@tanstack/react-router";
import { PhoneFrame, StatusBar } from "@/components/PhoneFrame";
import { Mail, Phone, Lock, User, Leaf, Globe, Trees, ChevronDown } from "lucide-react";
import { useState } from "react";
import { setPrefs } from "@/lib/user-prefs";

export const Route = createFileRoute("/auth")({
  component: Auth,
});

function Auth() {
  const [mode, setMode] = useState<"login" | "register">("login");
  const [method, setMethod] = useState<"email" | "phone">("email");
  const nav = useNavigate();
  const [fullName, setFullName] = useState("");
  const [language, setLanguage] = useState("English");
  const [park, setPark] = useState("Bwindi Impenetrable");

  const handleSubmit = () => {
    if (mode === "register") {
      setPrefs({
        fullName: fullName.trim() || "Friend",
        language,
        park,
      });
    }
    nav({ to: "/gps" });
  };

  return (
    <PhoneFrame>
      <div className="min-h-full flex flex-col bg-background">
        <div className="gradient-forest text-white pb-8 rounded-b-[36px] shadow-lg">
          <StatusBar dark />
          <div className="px-6 pt-4">
            <div className="flex items-center gap-2">
              <div className="h-10 w-10 rounded-xl bg-white/15 grid place-items-center"><Leaf size={20} className="text-accent" /></div>
              <span className="font-bold text-lg" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>Wildwatch</span>
            </div>
            <h1 className="text-2xl font-extrabold mt-6" style={{ fontFamily: "'Plus Jakarta Sans', sans-serif" }}>
              {mode === "login" ? "Welcome back" : "Create your account"}
            </h1>
            <p className="text-white/75 text-sm mt-1">
              {mode === "login" ? "Sign in to continue protecting wildlife." : "Join the conservation community."}
            </p>
          </div>
        </div>

        <div className="px-5 -mt-5">
          <div className="bg-card rounded-2xl p-1 shadow-card flex">
            {(["login", "register"] as const).map((m) => (
              <button key={m} onClick={() => setMode(m)}
                className={`flex-1 py-2.5 rounded-xl text-sm font-semibold capitalize ${mode === m ? "bg-primary text-primary-foreground" : "text-muted-foreground"}`}>
                {m === "login" ? "Sign in" : "Register"}
              </button>
            ))}
          </div>
        </div>

        <div className="px-5 mt-5 space-y-4">
          {/* Method toggle */}
          <div className="bg-secondary rounded-xl p-1 flex">
            {(["email", "phone"] as const).map((m) => (
              <button key={m} onClick={() => setMethod(m)}
                className={`flex-1 py-2 rounded-lg text-xs font-semibold capitalize flex items-center justify-center gap-1.5 ${method === m ? "bg-card text-foreground shadow-sm" : "text-muted-foreground"}`}>
                {m === "email" ? <Mail size={14} /> : <Phone size={14} />} {m}
              </button>
            ))}
          </div>

          {mode === "register" && (
            <Field icon={User} placeholder="Full name" value={fullName} onChange={setFullName} />
          )}
          <Field
            icon={method === "email" ? Mail : Phone}
            placeholder={method === "email" ? "you@example.com" : "+256 700 000 000"}
            type={method === "email" ? "email" : "tel"}
          />
          <Field icon={Lock} placeholder="Password" type="password" />

          {mode === "register" && (
            <>
              <SelectField icon={Globe} label="Preferred language" value={language} onChange={setLanguage}
                options={["English", "Luganda", "Runyankole", "Rukiga", "Lugbara", "Swahili"]} />
              <SelectField icon={Trees} label="National park" value={park} onChange={setPark}
                options={["Bwindi Impenetrable", "Mgahinga Gorilla", "Murchison Falls", "Queen Elizabeth", "Kibale", "Other / future parks"]} />
            </>
          )}

          {mode === "login" && (
            <div className="text-right -mt-2">
              <a className="text-xs font-semibold text-primary">Forgot password?</a>
            </div>
          )}

          <button
            onClick={handleSubmit}
            className="w-full bg-primary text-primary-foreground py-4 rounded-2xl font-semibold shadow-md hover:opacity-95">
            {mode === "login" ? "Sign in" : "Create account"}
          </button>

          <div className="flex items-center gap-3 py-1">
            <div className="flex-1 h-px bg-border" />
            <span className="text-[11px] text-muted-foreground">or continue with</span>
            <div className="flex-1 h-px bg-border" />
          </div>
          <div className="grid grid-cols-2 gap-2">
            <button className="py-3 rounded-xl border border-border bg-card text-sm font-semibold">Google</button>
            <button className="py-3 rounded-xl border border-border bg-card text-sm font-semibold">Apple</button>
          </div>

          <p className="text-center text-xs text-muted-foreground pt-2 pb-6">
            By continuing you agree to our <span className="text-primary font-semibold">Terms</span> and <span className="text-primary font-semibold">Privacy Policy</span>.
          </p>
        </div>
      </div>
    </PhoneFrame>
  );
}

function Field({ icon: Icon, placeholder, type = "text", value, onChange }: { icon: any; placeholder: string; type?: string; value?: string; onChange?: (v: string) => void }) {
  return (
    <div className="flex items-center gap-3 bg-card border border-border rounded-2xl px-4 py-3.5 focus-within:border-primary">
      <Icon size={18} className="text-muted-foreground" />
      <input
        type={type}
        placeholder={placeholder}
        value={value}
        onChange={onChange ? (e) => onChange(e.target.value) : undefined}
        className="flex-1 bg-transparent outline-none text-sm placeholder:text-muted-foreground"
      />
    </div>
  );
}

function SelectField({ icon: Icon, label, value, options, onChange }: { icon: any; label: string; value: string; options: string[]; onChange?: (v: string) => void }) {
  return (
    <div>
      <label className="text-[11px] font-semibold text-muted-foreground uppercase tracking-wide">{label}</label>
      <div className="mt-1.5 flex items-center gap-3 bg-card border border-border rounded-2xl px-4 py-3.5">
        <Icon size={18} className="text-muted-foreground" />
        <select
          value={value}
          onChange={onChange ? (e) => onChange(e.target.value) : undefined}
          className="flex-1 bg-transparent outline-none text-sm appearance-none">
          {options.map((o) => <option key={o}>{o}</option>)}
        </select>
        <ChevronDown size={16} className="text-muted-foreground" />
      </div>
    </div>
  );
}