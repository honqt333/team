/**
 * Builds the signature rows of a printed document.
 *
 * Every signature row carries a `show` flag: the workshop decides
 * which signatures are printed, and the print templates honour
 * `show !== false`.
 *
 * The two pages that build the print payload (the internal quote print
 * and the public quote page) used to copy the saved row and then force
 * `show: true` on it. The saved choice was therefore erased before it
 * reached the template, and a customer signature the workshop had
 * hidden was printed anyway.
 *
 * Only the URL is replaced here — the saved `show` is kept.
 */

/**
 * Is this signature row the customer's own signature?
 */
export const isCustomerSignature = (signature) => {
    const text =
        `${signature?.name_ar || ''} ${signature?.name_en || ''} ${signature?.name || ''}`.toLowerCase();

    return (
        text.includes('عميل') ||
        text.includes('العميل') ||
        text.includes('زبون') ||
        text.includes('customer') ||
        text.includes('client')
    );
};

/**
 * The signature rows of a printed document.
 *
 * @param {object} docSettings the document settings of the tenant
 * @param {string|null} customerSignatureUrl the URL of the signature the customer gave
 */
export const buildPrintSignatures = (docSettings, customerSignatureUrl) => {
    const saved = docSettings?.signatures;

    const list =
        saved && saved.length > 0
            ? saved.map((signature) => {
                  if (isCustomerSignature(signature)) {
                      // The URL is the one thing that changes here: the
                      // saved `show` is the workshop's decision and it
                      // is kept as it is.
                      return { ...signature, url: customerSignatureUrl || signature.url };
                  }

                  return signature;
              })
            : [
                  {
                      name_ar: 'توقيع المستشار الفني',
                      name_en: 'Service Advisor Signature',
                      show: true,
                  },
                  {
                      name_ar: 'توقيع العميل للاعتماد',
                      name_en: 'Customer Signature',
                      url: customerSignatureUrl,
                      show: true,
                  },
              ];

    // A customer signed, but the settings hold no row for it: add one.
    // This row is new, so it follows the default of being shown.
    if (customerSignatureUrl && !list.some((s) => isCustomerSignature(s) && s.url)) {
        list.push({
            name_ar: 'توقيع العميل للاعتماد',
            name_en: 'Customer Signature',
            url: customerSignatureUrl,
            show: true,
        });
    }

    return list;
};
