import sys
import os
import shutil
import pandas as pd
from PyPDF2 import PdfReader, PdfWriter

# ===============================
# PATH DASAR (KUNCI UTAMA)
# ===============================

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

STORAGE_DIR = os.path.join(BASE_DIR, "storage", "app")
UPLOAD_DIR = os.path.join(STORAGE_DIR, "upload")
TMP_DIR = os.path.join(STORAGE_DIR, "tmp")

# Pastikan folder ada
os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(TMP_DIR, exist_ok=True)

# ===============================
# UTILITIES
# ===============================

def read_excel_names(excel_path):
    df = pd.read_excel(excel_path)
    first_column = df.iloc[:, 0]

    names = []
    for value in first_column:
        if isinstance(value, str) and value.strip():
            names.append(value.strip())

    return names


def zip_folder(source_dir, zip_path):
    # Pastikan source tidak kosong
    if not os.listdir(source_dir):
        print("ERROR: Folder output kosong, tidak ada file untuk di-zip")
        sys.exit(1)

    base_name = zip_path.replace(".zip", "")
    shutil.make_archive(base_name, "zip", source_dir)

    # Bersihkan folder hasil split setelah zip
    shutil.rmtree(source_dir)


# ===============================
# MODE: SPLIT SAJA
# ===============================

def split_pdf_only(pdf_path):
    reader = PdfReader(pdf_path)

    output_dir = os.path.join(TMP_DIR, "output_split")
    os.makedirs(output_dir, exist_ok=True)

    for i, page in enumerate(reader.pages):
        writer = PdfWriter()
        writer.add_page(page)

        output_path = os.path.join(output_dir, f"page_{i + 1}.pdf")
        with open(output_path, "wb") as f:
            writer.write(f)

    zip_path = os.path.join(TMP_DIR, "result_split.zip")
    zip_folder(output_dir, zip_path)

    # WAJIB: print path zip agar Laravel bisa baca
    print(zip_path)


# ===============================
# MODE: SPLIT + RENAME
# ===============================

def split_pdf_and_rename(pdf_path, excel_path):
    names = read_excel_names(excel_path)
    reader = PdfReader(pdf_path)

    if len(reader.pages) != len(names):
        print(
            f"ERROR: Halaman PDF ({len(reader.pages)}) tidak sama dengan data Excel ({len(names)})"
        )
        sys.exit(1)

    output_dir = os.path.join(TMP_DIR, "output_rename")
    os.makedirs(output_dir, exist_ok=True)

    for i, page in enumerate(reader.pages):
        writer = PdfWriter()
        writer.add_page(page)

        safe_name = names[i].replace(" ", "_")
        output_path = os.path.join(output_dir, f"{safe_name}.pdf")

        with open(output_path, "wb") as f:
            writer.write(f)

    zip_path = os.path.join(TMP_DIR, "result_rename.zip")
    zip_folder(output_dir, zip_path)

    # WAJIB: print path zip agar Laravel bisa baca
    print(zip_path)


# ===============================
# MAIN
# ===============================

def main():
    if len(sys.argv) < 3:
        print("ERROR: Argumen tidak lengkap")
        sys.exit(1)

    mode = sys.argv[1]
    pdf_path = sys.argv[2]

    if not os.path.exists(pdf_path):
        print(f"ERROR: File PDF tidak ditemukan: {pdf_path}")
        sys.exit(1)

    if mode == "split":
        split_pdf_only(pdf_path)

    elif mode == "rename":
        if len(sys.argv) < 4:
            print("ERROR: Excel wajib untuk mode rename")
            sys.exit(1)

        excel_path = sys.argv[3]

        if not os.path.exists(excel_path):
            print(f"ERROR: File Excel tidak ditemukan: {excel_path}")
            sys.exit(1)

        split_pdf_and_rename(pdf_path, excel_path)

    else:
        print("ERROR: Mode tidak dikenal (gunakan split atau rename)")
        sys.exit(1)


if __name__ == "__main__":
    main()
