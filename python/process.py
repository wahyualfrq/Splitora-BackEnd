import sys
import os
import shutil
import pandas as pd
from PyPDF2 import PdfReader, PdfWriter

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

STORAGE_DIR = os.path.join(BASE_DIR, "storage", "app")
UPLOAD_DIR = os.path.join(STORAGE_DIR, "upload")
TMP_DIR = os.path.join(STORAGE_DIR, "tmp")

os.makedirs(UPLOAD_DIR, exist_ok=True)
os.makedirs(TMP_DIR, exist_ok=True)


def is_number(value):
    return isinstance(value, (int, float)) and not pd.isna(value)


def clean_name(value):
    if isinstance(value, str):
        value = value.strip()
        if value:
            return value
    return None


def detect_name_column(df):
    columns_lower = [str(col).strip().lower() for col in df.columns]

    for idx, col in enumerate(columns_lower):
        if "nama" in col or "name" in col:
            return idx

    first_col = df.iloc[:, 0]
    sample_value = next((v for v in first_col if not pd.isna(v)), None)

    if is_number(sample_value):
        if df.shape[1] >= 2:
            return 1
        raise ValueError("Tidak ditemukan kolom nama")

    return 0


def read_excel_names(excel_path):
    df = pd.read_excel(excel_path)

    if df.empty:
        raise ValueError("File Excel kosong")

    name_col_index = detect_name_column(df)
    name_column = df.iloc[:, name_col_index]

    names = []
    for value in name_column:
        name = clean_name(value)
        if name:
            names.append(name)

    if not names:
        raise ValueError("Tidak ada data nama yang valid")

    return names


def zip_folder(source_dir, zip_path):
    if not os.listdir(source_dir):
        print("ERROR: Folder output kosong")
        sys.exit(1)

    base_name = zip_path.replace(".zip", "")
    shutil.make_archive(base_name, "zip", source_dir)
    shutil.rmtree(source_dir)


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
    print(zip_path)


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
    print(zip_path)


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
        return

    if mode == "rename":
        if len(sys.argv) < 4:
            print("ERROR: Excel wajib untuk mode rename")
            sys.exit(1)

        excel_path = sys.argv[3]

        if not os.path.exists(excel_path):
            print(f"ERROR: File Excel tidak ditemukan: {excel_path}")
            sys.exit(1)

        split_pdf_and_rename(pdf_path, excel_path)
        return

    print("ERROR: Mode tidak dikenal")
    sys.exit(1)


if __name__ == "__main__":
    main()